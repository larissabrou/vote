<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Payment;
use App\Services\AfribapayService;
use App\Services\EmailService;
use App\Services\EmailValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected EmailService $emailService,
        protected AfribapayService $afribapayService,
        protected EmailValidationService $emailValidationService
    ) {
    }

    /**
     * Affiche le formulaire de paiement pour activer une élection (envoyer les emails aux votants).
     */
    public function showActivateElection(Election $election)
    {
        if (Auth::guard('web')->id() !== $election->user_id) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if (!$election->isActivated()) {
            // Auto-rattrapage: si un paiement est déjà marqué "completed",
            // activer l'élection sans redemander un nouveau paiement.
            $completedPayment = Payment::where('election_id', $election->id)
                ->where('status', Payment::STATUS_COMPLETED)
                ->latest('paid_at')
                ->latest('id')
                ->first();
            if ($completedPayment) {
                $this->completePaymentAndActivateElection($completedPayment);
                return redirect()->route('elections.voir', $election)
                    ->with('success', 'Paiement déjà confirmé. L\'élection a été activée automatiquement.');
            }
        }
        if ($election->isActivated()) {
            return redirect()->route('elections.voir', $election)->with('info', 'Cette élection est déjà activée.');
        }
        if ($election->hasEnded()) {
            return redirect()->route('elections.voir', $election)->with('error', 'La date ou l\'heure de l\'élection est passée. Modifiez la date et/ou l\'heure de l\'élection pour pouvoir payer.');
        }

        $election->load('voters');
        $emailsWithInvalidDomain = $this->emailValidationService->getEmailsWithInvalidDomain(
            $election->voters->pluck('email')
        );

        return view('payment.election', [ 
            'election' => $election,
            'amount' => Payment::AMOUNT_ELECTION_FCFA,
            'currency' => 'FCFA',
            'paymentMethods' => Payment::getPaymentMethods(),
            'emailsWithInvalidDomain' => $emailsWithInvalidDomain,
        ]);
    }

    /**
     * Initie le paiement pour activer une élection (election_id requis).
     */
    public function initiate(Request $request)
    {
        Log::info('[Payment] initiate: requête reçue', [
            'election_id' => $request->election_id,
            'payment_method' => $request->payment_method,
        ]);

        if (!Auth::guard('web')->check()) {
            Log::warning('[Payment] initiate: utilisateur non authentifié');
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $methods = array_keys(Payment::getPaymentMethods());
        $rules = [
            'phone' => 'required|string|min:8|max:20',
            'election_id' => 'required|exists:elections,id',
            'payment_method' => 'required|in:' . implode(',', $methods),
        ];
        if ($request->payment_method === Payment::METHOD_ORANGE_MONEY) {
            $rules['otp'] = 'nullable|string|min:4|max:8';
        }
        $request->validate($rules);
        Log::info('[Payment] initiate: validation OK');

        $election = Election::findOrFail($request->election_id);
        if ($election->user_id !== Auth::id()) {
            Log::warning('[Payment] initiate: élection non autorisée', ['election_id' => $election->id]);
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }
        if ($election->isActivated()) {
            Log::info('[Payment] initiate: élection déjà activée', ['election_id' => $election->id]);
            return response()->json(['error' => 'Cette élection est déjà activée.'], 400);
        }
        $completedPayment = Payment::where('election_id', $election->id)
            ->where('status', Payment::STATUS_COMPLETED)
            ->latest('paid_at')
            ->latest('id')
            ->first();
        if ($completedPayment) {
            Log::info('[Payment] initiate: paiement déjà complété, auto-activation', [
                'election_id' => $election->id,
                'payment_id' => $completedPayment->id,
            ]);
            $this->completePaymentAndActivateElection($completedPayment);
            return response()->json([
                'success' => true,
                'payment_completed' => true,
                'redirect_url' => route('elections.voir', $election),
                'message' => 'Paiement déjà confirmé. L\'élection est activée.',
            ]);
        }
        if ($election->hasEnded()) {
            Log::info('[Payment] initiate: date/heure élection passée', ['election_id' => $election->id]);
            return response()->json(['error' => 'La date ou l\'heure de l\'élection est passée. Modifiez la date et/ou l\'heure de l\'élection pour pouvoir payer.'], 400);
        }

        $payment = new Payment();
        $payment->amount = Payment::AMOUNT_ELECTION_FCFA;
        $payment->currency = 'XOF';
        $payment->status = Payment::STATUS_PENDING;
        $payment->phone = $request->phone;
        $payment->reference = Payment::generateReference();
        $payment->payment_method = $request->payment_method;
        $payment->election_id = $election->id;
        if ($request->payment_method === Payment::METHOD_ORANGE_MONEY && $request->filled('otp')) {
            $payment->otp = $request->otp;
        }
        $payment->save();

        Log::info('[Payment] initiate: paiement créé', [
            'payment_id' => $payment->id,
            'reference' => $payment->reference,
            'payment_method' => $payment->payment_method,
            'election_id' => $payment->election_id,
        ]);

        $redirectUrl = route('paiement.confirmer', $payment);

        if (config('afribapay.enabled')) {
            Log::info('[Payment] initiate: appel Afribapay payin', ['payment_id' => $payment->id]);
            $result = $this->afribapayService->payin($payment);

            Log::info('[Payment] initiate: réponse Afribapay', [
                'payment_id' => $payment->id,
                'status' => $result['status'] ?? null,
                'reference' => $result['reference'] ?? null,
            ]);

            if (!empty($result['reference'])) {
                $payment->provider_order_id = $result['reference'];
                $payment->save();
            }
            if ($result['status'] === 'SUCCESS') {
                Log::info('[Payment] initiate: paiement SUCCESS, activation élection', ['payment_id' => $payment->id]);
                $this->completePaymentAndActivateElection($payment);
                $target = $payment->election_id
                    ? route('elections.voir', $payment->election_id)
                    : route('elections.liste');
                return response()->json([
                    'success' => true,
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'redirect_url' => $target,
                    'payment_completed' => true,
                ]);
            }
            if ($result['status'] === 'PENDING') {
                Log::info('[Payment] initiate: paiement PENDING (Wave)', [
                    'payment_id' => $payment->id,
                    'wave_launch_url_present' => !empty($result['wave_launch_url']),
                    'wave_launch_url' => $result['wave_launch_url'] ?? null,
                ]);
                $json = [
                    'success' => true,
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'redirect_url' => $redirectUrl,
                ];
                if (!empty($result['wave_launch_url'])) {
                    $json['wave_launch_url'] = $result['wave_launch_url'];
                }
                return response()->json($json);
            }
            Log::warning('[Payment] initiate: échec Afribapay', [
                'payment_id' => $payment->id,
                'message' => $result['message'] ?? null,
                'http_code' => $result['http_code'] ?? null,
            ]);
            return response()->json([
                'error' => $result['message'] ?? 'Échec du paiement Afribapay',
            ], (int) ($result['http_code'] ?? 500));
        }

        Log::info('[Payment] initiate: Afribapay désactivé, paiement en attente', ['payment_id' => $payment->id]);
        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Marque le paiement comme complété et active l'élection (emails aux votants).
     */
    protected function completePaymentAndActivateElection(Payment $payment): void
    {
        $payment->status = Payment::STATUS_COMPLETED;
        $payment->paid_at = now();
        $payment->save();

        $election = $payment->election;
        if ($election) {
            // Après un paiement confirmé, l'élection doit être activée systématiquement.
            $election->status = Election::STATUS_ACTIVE;
            $election->load('voters');
            // Éviter les envois en double lors d'un auto-rattrapage d'activation.
            if (($election->voters_source ?? 'link') === 'excel' && empty($election->emails_sent_at)) {
                foreach ($election->voters as $voter) {
                    try {
                        $this->emailService->sendVotingLink($voter);
                        $voter->email_sent_at = now();
                        $voter->email_error = null;
                    } catch (\Exception $e) {
                        \Log::warning("Erreur envoi email votant {$voter->email}: " . $e->getMessage());
                        $voter->email_sent_at = null;
                        $voter->email_error = $e->getMessage();
                    }
                    $voter->save();
                }
            }
            $election->emails_sent_at = now();
            $election->save();
        }
    }

    private function activationSuccessMessage(Election $election): string
    {
        if (($election->voters_source ?? 'link') === 'excel') {
            return 'Paiement effectué avec succès. Les liens de vote ont été envoyés automatiquement aux votants importés.';
        }

        return 'Paiement effectué avec succès. L\'élection est activée. Une fois les inscriptions terminées, allez dans la liste des votants et cliquez sur « Renvoyer les liens » pour l\'envoi en masse.';
    }

    /**
     * Vérifie le statut de la transaction (BD + API Afribapay). Utilisé par le polling après clic sur Payer.
     */
    public function checkTransactionStatus(Payment $payment)
    {
        if (!Auth::guard('web')->check()) {
            return response()->json(['status' => 'failed', 'message' => 'Non autorisé.'], 403);
        }
        if ($payment->election && $payment->election->user_id !== Auth::id()) {
            return response()->json(['status' => 'failed', 'message' => 'Non autorisé.'], 403);
        }

        if ($payment->status === Payment::STATUS_COMPLETED) {
            return response()->json([
                'status' => 'completed',
                'redirect_url' => $payment->election_id
                    ? route('elections.voir', $payment->election_id)
                    : route('elections.liste'),
            ]);
        }

        if ($payment->status === Payment::STATUS_FAILED) {
            return response()->json(['status' => 'failed', 'message' => 'Le paiement a échoué.']);
        }

        if (config('afribapay.enabled')) {
            $orderId = $payment->provider_order_id ?: $payment->reference;
            $result = $this->afribapayService->getStatus($orderId);

            if ($result['status'] === 'SUCCESS') {
                $this->completePaymentAndActivateElection($payment);
                return response()->json([
                    'status' => 'completed',
                    'redirect_url' => $payment->election_id
                        ? route('elections.voir', $payment->election_id)
                        : route('elections.liste'),
                ]);
            }
            if ($result['status'] === 'FAILED') {
                $payment->status = Payment::STATUS_FAILED;
                $payment->save();
                return response()->json(['status' => 'failed', 'message' => 'La transaction a échoué. Veuillez réessayer.']);
            }
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Ancienne page de validation de paiement : redirection vers l'élection.
     * Le flux reste sur la page d'initiation (loader + polling).
     */
    public function confirm(Payment $payment)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($payment->election && $payment->election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }

        $target = $payment->election_id
            ? route('elections.voir', $payment->election_id)
            : route('elections.liste');
        $message = $payment->isCompleted()
            ? 'Ce paiement a déjà été validé.'
            : ($payment->status === Payment::STATUS_FAILED ? 'Paiement échoué.' : 'Retour à l\'élection.');
        return redirect($target)->with('info', $message);
    }

    /**
     * Simule la confirmation du paiement puis envoie les emails et active l'élection.
     */
    public function confirmSimulate(Payment $payment)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($payment->election && $payment->election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            return redirect()->route('elections.liste')->with('error', 'Paiement déjà traité.');
        }

        $this->completePaymentAndActivateElection($payment);

        $election = $payment->election;
        if ($election) {
            return redirect()->route('elections.voir', $election)->with('success', $this->activationSuccessMessage($election));
        }
        return redirect()->route('elections.liste')->with('success', 'Paiement enregistré.');
    }

    /**
     * Retour Wave succès (return_url avec ?transactionId= référence).
     * Vérifie le statut auprès d'Afribapay et active l'élection si le paiement est réussi (au cas où le webhook n'a pas été reçu).
     */
    public function waveReturnSuccess(Request $request)
    {
        $transactionId = $request->query('transactionId');
        Log::info('[Afribapay Retour] return_url (succès)', [
            'transactionId' => $transactionId,
            'query' => $request->query(),
        ]);

        $payment = $transactionId ? Payment::where('reference', $transactionId)->first() : null;
        $target = route('elections.liste');
        if ($payment && $payment->election_id) {
            if (Auth::check() && $payment->election->user_id === Auth::id()) {
                $target = route('elections.voir', $payment->election_id);
            }
        }

        if ($payment) {
            if ($payment->status === Payment::STATUS_COMPLETED) {
                Log::info('[Afribapay Retour] Paiement déjà complété (webhook)', ['payment_id' => $payment->id]);
                $election = $payment->election;
                if ($election) {
                    return redirect($target)->with('success', $this->activationSuccessMessage($election));
                }
                return redirect($target)->with('success', 'Paiement effectué avec succès.');
            }
            if ($payment->status === Payment::STATUS_FAILED) {
                Log::info('[Afribapay Retour] Paiement en échec', ['payment_id' => $payment->id]);
                return redirect($target)->with('error', 'Le paiement a échoué. Vous pouvez réessayer depuis la page de l\'élection.');
            }
            // Paiement encore en attente : vérifier le statut auprès d'Afribapay (au cas où le webhook n'a pas été reçu)
            $orderId = $payment->provider_order_id ?: $payment->reference;
            $result = $this->afribapayService->getStatus($orderId);
            Log::info('[Afribapay Retour] Vérification statut', ['payment_id' => $payment->id, 'status' => $result['status']]);
            if ($result['status'] === 'SUCCESS') {
                $this->completePaymentAndActivateElection($payment);
                Log::info('[Afribapay Retour] Élection activée après retour Wave', ['payment_id' => $payment->id, 'election_id' => $payment->election_id]);
                $election = $payment->election;
                if ($election) {
                    return redirect($target)->with('success', $this->activationSuccessMessage($election));
                }
                return redirect($target)->with('success', 'Paiement effectué avec succès.');
            }
            if ($result['status'] === 'FAILED') {
                $payment->status = Payment::STATUS_FAILED;
                $payment->save();
                return redirect($target)->with('error', 'Le paiement a échoué. Vous pouvez réessayer depuis la page de l\'élection.');
            }
        }

        Log::info('[Afribapay Retour] Redirection succès', ['transactionId' => $transactionId, 'target' => $target]);
        return redirect($target)->with('success', 'Paiement effectué. Les liens de vote seront envoyés aux votants une fois la transaction confirmée.');
    }

    /**
     * Retour Wave échec/annulation (cancel_url avec ?transactionId= référence). Redirige vers la page de paiement avec message d'échec et invitation à réessayer.
     */
    public function waveReturnError(Request $request)
    {
        $transactionId = $request->query('transactionId');
        Log::info('[Afribapay Retour] cancel_url (échec/annulation)', [
            'transactionId' => $transactionId,
            'query' => $request->query(),
        ]);

        $payment = $transactionId ? Payment::where('reference', $transactionId)->first() : null;
        // En cas d'échec : rediriger vers la page de paiement de l'élection pour réessayer
        if ($payment && $payment->election_id) {
            $election = $payment->election;
            if ($election && Auth::check() && $election->user_id === Auth::id()) {
                Log::info('[Afribapay Retour] Redirection échec vers page paiement', ['transactionId' => $transactionId, 'election_id' => $election->id]);
                return redirect()->route('paiement.election.activer', $election)
                    ->with('error', 'Paiement annulé ou échoué. Veuillez réessayer en cliquant sur « Payer » ci-dessous.');
            }
        }
        // Fallback : liste des élections
        $target = route('elections.liste');
        Log::info('[Afribapay Retour] Redirection échec vers liste', ['transactionId' => $transactionId, 'target' => $target]);
        return redirect($target)->with('error', 'Paiement annulé ou échoué. Vous pouvez réessayer depuis la page de l\'élection.');
    }

    /**
     * Retour Wave après paiement réussi (return_url avec id dans l’URL, ancien format).
     */
    public function waveReturn(Payment $payment)
    {
        if ($payment->election && $payment->election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        $target = $payment->election_id
            ? route('elections.voir', $payment->election_id)
            : route('elections.liste');
        return redirect($target)->with('info', 'Paiement en cours de validation. Les liens de vote seront envoyés une fois confirmé.');
    }

    /**
     * Annulation Wave (cancel_url avec id dans l’URL, ancien format).
     */
    public function waveCancel(Payment $payment)
    {
        if ($payment->election && $payment->election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        $target = $payment->election_id
            ? route('elections.voir', $payment->election_id)
            : route('elections.liste');
        return redirect($target)->with('error', 'Paiement Wave annulé. Vous pouvez réessayer.');
    }
}

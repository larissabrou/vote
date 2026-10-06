<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AfribapayWebhookController extends Controller
{
    public function __construct(protected EmailService $emailService)
    {
    }

    /**
     * Webhook appelé par Afribapay / agrégateur (notify_url).
     * Payload : reference (format VOTE-..., notre référence), transaction_id (optionnel), status (SUCCESS | FAILED | CANCELLED | EXPIRED).
     */
    public function notify(Request $request)
    {
        $payload = $request->all();
        Log::info('[Afribapay Webhook] Réception', [
            'payload' => $payload,
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
        ]);

        // reference = notre référence (VOTE-xxx), order_id / reference_id = variantes possibles
        $orderId = $request->input('order_id') ?? $request->input('reference_id') ?? $request->input('reference');
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');

        if (!$orderId && !$transactionId) {
            Log::warning('[Afribapay Webhook] Aucun identifiant (order_id, reference_id, reference ou transaction_id)', ['body' => $payload]);
            return response()->json(['ok' => false, 'message' => 'order_id, reference ou transaction_id manquant'], 400);
        }

        // Recherche du paiement : par reference (notre référence type VOTE-xxx) ou par provider_order_id (réf. opérateur / transaction_id)
        $payment = null;
        if ($orderId) {
            $payment = Payment::where('reference', $orderId)
                ->orWhere('provider_order_id', $orderId)
                ->first();
        }
        if (!$payment && $transactionId) {
            $payment = Payment::where('provider_order_id', $transactionId)
                ->orWhere('reference', $transactionId)
                ->first();
        }

        if (!$payment) {
            Log::warning('[Afribapay Webhook] Paiement non trouvé', [
                'order_id' => $orderId,
                'transaction_id' => $transactionId,
                'payload' => $payload,
            ]);
            return response()->json(['ok' => false, 'message' => 'Paiement inconnu'], 404);
        }

        // Mise à jour optionnelle de provider_order_id si reçu et pas encore enregistré
        if ($transactionId && $payment->provider_order_id !== $transactionId) {
            $payment->provider_order_id = $transactionId;
            $payment->save();
        }

        Log::info('[Afribapay Webhook] Paiement trouvé', [
            'payment_id' => $payment->id,
            'reference' => $payment->reference,
            'current_status' => $payment->status,
            'incoming_status' => $status,
        ]);

        if ($payment->status === Payment::STATUS_COMPLETED) {
            Log::info('[Afribapay Webhook] Déjà traité (completed)', ['payment_id' => $payment->id]);
            return response()->json(['ok' => true, 'message' => 'Déjà traité']);
        }

        $statusUpper = strtoupper((string) $status);
        if ($statusUpper === 'SUCCESS') {
            Log::info('[Afribapay Webhook] Statut SUCCESS → mise à jour statut et activation élection', ['payment_id' => $payment->id]);
            $this->completePaymentAndActivateElection($payment);
        } elseif (in_array($statusUpper, ['FAILED', 'CANCELLED', 'EXPIRED'], true)) {
            Log::info('[Afribapay Webhook] Statut échec → marqué failed', ['payment_id' => $payment->id, 'status' => $status]);
            $payment->status = Payment::STATUS_FAILED;
            $payment->save();
        } else {
            Log::info('[Afribapay Webhook] Statut non géré', ['payment_id' => $payment->id, 'status' => $status]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Marque le paiement comme complété et active l'élection (envoi des emails aux votants).
     */
    protected function completePaymentAndActivateElection(Payment $payment): void
    {
        $payment->status = Payment::STATUS_COMPLETED;
        $payment->paid_at = now();
        $payment->save();

        $election = $payment->election;
        if ($election && !$election->isActivated()) {
            $election->load('voters');
            foreach ($election->voters as $voter) {
                try {
                    $this->emailService->sendVotingLink($voter);
                    $voter->email_sent_at = now();
                    $voter->email_error = null;
                } catch (\Exception $e) {
                    Log::warning("Erreur envoi email votant {$voter->email}: " . $e->getMessage());
                    $voter->email_sent_at = null;
                    $voter->email_error = $e->getMessage();
                }
                $voter->save();
            }
            $election->emails_sent_at = now();
            $election->save();
            Log::info('[Afribapay Webhook] Élection activée', ['election_id' => $election->id]);
        }
    }
}

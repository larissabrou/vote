<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfribapayService
{
    public function __construct(
        protected string $baseUrl,
        protected string $merchantKey,
        protected string $notifyUrl,
        protected ?string $tokenUrl,
        protected ?string $clientId,
        protected ?string $clientSecret,
        protected string $country = 'CI',
        protected string $lang = 'fr',
        protected ?string $tokenBasic = null,
        protected ?string $callbackBaseUrl = null
    ) {
    }

    /**
     * Option de vérification SSL pour les appels HTTP (comme CURLOPT_CAINFO => storage_path('cacert.pem') dans l'exemple).
     * Évite cURL 60 "unable to get local issuer certificate" en local.
     */
    protected function getHttpVerifyOption(): bool|string
    {
        $ca = config('afribapay.ca_file');
        if ($ca !== '' && $ca !== null) {
            $path = str_starts_with($ca, '/') || str_contains($ca, ':') ? $ca : storage_path($ca);
            if (is_file($path)) {
                return $path;
            }
        }
        // Même logique que l'exemple payin : storage_path('cacert.pem') si présent
        if (is_file(storage_path('cacert.pem'))) {
            return storage_path('cacert.pem');
        }
        return config('afribapay.verify_ssl', true) ? true : false;
    }

    /**
     * Récupère le token d'accès Afribapay (access_token) — même logique que l'exemple cURL.
     * POST /v1/token avec Authorization: Basic, réponse : data['data']['access_token'].
     */
    public function getAfribapayAccessToken(): array
    {
        $basicAuth = $this->tokenBasic
            ? $this->tokenBasic
            : ($this->clientId && $this->clientSecret ? base64_encode($this->clientId . ':' . $this->clientSecret) : null);

        if (!$basicAuth) {
            Log::warning('[Afribapay] Token non configuré (AFRIBAPAY_TOKEN_BASIC ou CLIENT_ID+CLIENT_SECRET)');
            return ['token' => null];
        }

        $tokenUrl = $this->tokenUrl ?: rtrim($this->baseUrl, '/') . '/v1/token';

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $tokenUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . $basicAuth,
            ],
        ];
        if (is_file(storage_path('cacert.pem'))) {
            $opts[CURLOPT_SSL_VERIFYHOST] = 2;
            $opts[CURLOPT_SSL_VERIFYPEER] = true;
            $opts[CURLOPT_CAINFO] = storage_path('cacert.pem');
        }
        curl_setopt_array($curl, $opts);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            Log::error('[Afribapay] getToken CURL error: ' . $error);
            return ['token' => null];
        }

        $data = json_decode($response, true);
        if (isset($data['data']['access_token'])) {
            return ['token' => $data['data']['access_token']];
        }
        Log::warning('[Afribapay] Échec récupération token', ['response' => $data]);
        return ['token' => null];
    }

    /**
     * Map payment_method (internal) vers opérateur API Afribapay.
     */
    public static function mapOperator(string $paymentMethod): ?string
    {
        return match ($paymentMethod) {
            Payment::METHOD_ORANGE_MONEY => 'orange',
            Payment::METHOD_MOOV => 'moov',
            Payment::METHOD_MTN_MOMO => 'mtn',
            Payment::METHOD_WAVE => 'wave',
            default => null,
        };
    }

    /**
     * Lance un payin Afribapay pour un paiement donné.
     * Retourne: ['success' => bool, 'status' => 'SUCCESS'|'PENDING'|'ERROR', 'reference' => ?, 'wave_launch_url' => ?, 'message' => ?, 'http_code' => ?]
     */
    public function payin(Payment $payment): array
    {
        $operator = self::mapOperator($payment->payment_method);
        if (!$operator) {
            return [
                'success' => false,
                'status' => 'ERROR',
                'message' => 'Opérateur non reconnu',
                'http_code' => 400,
            ];
        }

        $result = $this->getAfribapayAccessToken();
        $authorization_token = $result['token'] ?? null;
        if (!$authorization_token) {
            return [
                'success' => false,
                'status' => 'ERROR',
                'message' => 'Impossible d\'obtenir le token Afribapay',
                'http_code' => 503,
            ];
        }

        $order_id = $payment->reference;
        $phone_number = trim(preg_replace('/\s+/', '', (string) ($payment->phone ?? '')));
        $amount = (int) $payment->amount;
        $currency = $payment->currency ?? 'XOF';

        $data = [
            'country' => $this->country,
            'operator' => $operator,
            'phone_number' => $phone_number,
            'amount' => $amount,
            'currency' => $currency,
            'order_id' => $order_id,
            'merchant_key' => $this->merchantKey,
            'notify_url' => $this->notifyUrl,
            'reference_id' => 'Vote_' . $order_id,
            'lang' => $this->lang,
        ];

        if ($operator === 'orange' && !empty($payment->otp)) {
            $data['otp_code'] = $payment->otp;
        }

        // Wave : return_url et cancel_url uniquement (l'API Afribapay rejette success_url/error_url).
        if ($operator === 'wave') {
            $base = ($this->callbackBaseUrl !== null && $this->callbackBaseUrl !== '')
                ? rtrim($this->callbackBaseUrl, '/')
                : null;
            if ($base !== null && $base !== '') {
                if (str_starts_with($base, 'http://')) {
                    $base = 'https://' . substr($base, 7);
                }
                $data['return_url'] = $base . route('paiement.retour.succes', ['transactionId' => $order_id], false);
                $data['cancel_url'] = $base . route('paiement.retour.echec', ['transactionId' => $order_id], false);
            } else {
                $data['return_url'] = url()->route('paiement.retour.succes', ['transactionId' => $order_id]);
                $data['cancel_url'] = url()->route('paiement.retour.echec', ['transactionId' => $order_id]);
            }
        }

        $json_data = json_encode($data);
        $url = rtrim($this->baseUrl, '/') . '/v1/pay/payin';

        Log::info('[Afribapay] Payin request', [
            'order_id' => $order_id,
            'operator' => $operator,
            'return_url' => $data['return_url'] ?? null,
            'cancel_url' => $data['cancel_url'] ?? null,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $authorization_token,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        if (is_file(storage_path('cacert.pem'))) {
            curl_setopt($ch, CURLOPT_CAINFO, storage_path('cacert.pem'));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            curl_close($ch);
            Log::error('[Afribapay] Payin CURL error: ' . $curlError);
            $isTimeout = (stripos($curlError, 'timeout') !== false || stripos($curlError, 'timed out') !== false);
            $message = $isTimeout
                ? 'Le délai d\'attente est dépassé (1 minute). Veuillez réessayer.'
                : 'Le service de paiement est temporairement indisponible. Veuillez réessayer dans quelques instants ou choisir un autre opérateur.';
            return [
                'success' => false,
                'status' => 'ERROR',
                'message' => $message,
                'http_code' => 500,
            ];
        }
        curl_close($ch);

        Log::info('[Afribapay] Payin response', [
            'httpCode' => $httpCode,
            'raw_response' => $response,
            'decoded_data' => $data,
        ]);

        $decoded = json_decode($response, true);
        $data = $decoded['data'] ?? [];

        if ($httpCode === 200 && isset($data['status'])) {
            if ($data['status'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'status' => 'SUCCESS',
                    'reference' => $data['order_id'] ?? $order_id,
                    'message' => 'Transaction payin réussie',
                    'http_code' => 200,
                ];
            }
            if ($data['status'] === 'PENDING') {
                $out = [
                    'success' => true,
                    'status' => 'PENDING',
                    'reference' => $data['order_id'] ?? $order_id,
                    'message' => 'Transaction en attente de confirmation',
                    'http_code' => 202,
                ];
                if ($operator === 'wave' && !empty($data['provider_link'])) {
                    $out['wave_launch_url'] = $data['provider_link'];
                }
                return $out;
            }
        }

        $message = $data['message'] ?? $decoded['error']['message'] ?? $decoded['message'] ?? 'Réponse inattendue Afribapay';
        Log::warning('[Afribapay] Échec ou réponse inattendue', ['response_decoded' => $data, 'http_code' => $httpCode]);

        // Toujours afficher un message en français à l'utilisateur (l'API peut renvoyer de l'anglais)
        $userMessage = 'Le paiement n\'a pas abouti. Veuillez réessayer.';
        if ($httpCode >= 500 || stripos($message, 'Non-JSON') !== false || stripos($message, 'from provider') !== false) {
            $userMessage = 'Le service de paiement (opérateur) est temporairement indisponible. Veuillez réessayer ou choisir un autre opérateur.';
        } elseif (stripos($message, 'Network instability') !== false || stripos($message, 'network instability') !== false) {
            $userMessage = 'Le réseau de l\'opérateur est temporairement instable. Veuillez réessayer dans quelques instants ou choisir un autre opérateur.';
        }

        return [
            'success' => false,
            'status' => 'ERROR',
            'message' => $userMessage,
            'http_code' => $httpCode,
            'data' => $data,
        ];
    }

    /**
     * Vérifie le statut d'une transaction Afribapay (GET /v1/status?order_id=...).
     * Retourne: ['status' => 'SUCCESS'|'PENDING'|'FAILED', 'http_code' => int]
     */
    public function getStatus(string $orderId): array
    {
        $result = $this->getAfribapayAccessToken();
        $token = $result['token'] ?? null;
        if (!$token) {
            Log::warning('[Afribapay] getStatus: token indisponible');
            return ['status' => 'PENDING', 'http_code' => 503];
        }

        $url = rtrim($this->baseUrl, '/') . '/v1/status?order_id=' . urlencode($orderId);
        Log::info('[Afribapay] getStatus request', ['order_id' => $orderId]);

        try {
            $response = Http::withToken($token)
                ->withOptions(['verify' => $this->getHttpVerifyOption(), 'timeout' => 60])
                ->get($url);

            $httpCode = $response->status();
            $body = $response->json();
            $data = $body['data'] ?? [];
            $status = $data['status'] ?? null;

            Log::info('[Afribapay] getStatus response', ['http_code' => $httpCode, 'status' => $status]);

            if ($status === null) {
                return ['status' => 'PENDING', 'http_code' => $httpCode];
            }

            $normalized = strtoupper((string) $status);
            if ($normalized === 'SUCCESS' || $normalized === 'SUCCESSFUL') {
                return ['status' => 'SUCCESS', 'http_code' => 200];
            }
            if (in_array($normalized, ['FAILED', 'CANCELLED', 'EXPIRED', 'ERROR'], true)) {
                return ['status' => 'FAILED', 'http_code' => 200];
            }

            return ['status' => 'PENDING', 'http_code' => $httpCode];
        } catch (\Throwable $e) {
            Log::error('[Afribapay] Exception getStatus: ' . $e->getMessage());
            return ['status' => 'PENDING', 'http_code' => 500];
        }
    }
}

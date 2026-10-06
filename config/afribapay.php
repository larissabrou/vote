<?php

return [
    'enabled' => (bool) env('AFRIBAPAY_ENABLED', false),

    'base_url' => env('AFRIBAPAY_BASE_URL', 'https://api.afribapay.com'),

    'merchant_key' => env('AFRIBAPAY_MERCHANT_KEY', 'mk_54056405Ur250603123754'),

    'notify_url' => env('AFRIBAPAY_NOTIFY_URL', 'https://webhook.toutransfert.com/afribapay1/index.php'),

    'country' => env('AFRIBAPAY_COUNTRY', 'CI'),

    'lang' => env('AFRIBAPAY_LANG', 'fr'),

    // Token (si l’API utilise OAuth2 / client_credentials)
    'token_url' => env('AFRIBAPAY_TOKEN_URL', ''), // vide = base_url/v1/token
    'client_id' => env('AFRIBAPAY_CLIENT_ID', ''),
    'client_secret' => env('AFRIBAPAY_CLIENT_SECRET', ''),
    // Si défini, utilisé pour Authorization: Basic (sinon base64(client_id:client_secret))
    'token_basic' => env('AFRIBAPAY_TOKEN_BASIC', 'cGtfYTE5YzdiY2E2OTljNjA1Njc5MWM2YmZjMDhiYjhlZGE6c2tfcndnMnliZkN6am54eFdrZGVS'),

    // SSL : comme l'exemple (CURLOPT_CAINFO => storage_path('cacert.pem')). Mettre cacert.pem dans storage/
    'verify_ssl' => env('AFRIBAPAY_VERIFY_SSL', true),
    'ca_file' => env('AFRIBAPAY_CA_FILE', 'cacert.pem'), // fichier dans storage/ ou chemin absolu

    // URL de base pour return_url/cancel_url (Wave) — DOIT être l'URL de VOTRE site (où l'utilisateur doit revenir après paiement), pas Afribapay.
    // Ex: https://votredomaine.com — sans slash final. En production, définir AFRIBAPAY_CALLBACK_BASE_URL dans .env.
    'callback_base_url' => env('AFRIBAPAY_CALLBACK_BASE_URL', ''),
];

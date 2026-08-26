<?php
return [
    'paypal' => ['env' => env('PAYPAL_ENV', 'sandbox'), 'client_id' => env('PAYPAL_CLIENT_ID'), 'client_secret' => env('PAYPAL_CLIENT_SECRET'), 'webhook_id' => env('PAYPAL_WEBHOOK_ID')],
    'spotify' => ['client_id' => env('SPOTIFY_CLIENT_ID'), 'client_secret' => env('SPOTIFY_CLIENT_SECRET'), 'redirect' => env('SPOTIFY_REDIRECT_URI')],
    'fcm' => ['project_id' => env('FCM_PROJECT_ID'), 'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON')],
    'openai' => ['base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), 'api_key' => env('OPENAI_API_KEY'), 'model' => env('OPENAI_MODEL', 'gpt-5.4')],
];

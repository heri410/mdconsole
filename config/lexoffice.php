<?php

return [
    'api_key' => env('LEXOFFICE_API_KEY'),
    'api_base_url' => env('LEXOFFICE_API_BASE_URL', 'https://api.lexware.io/v1'),
    'webhook_url' => env('LEXOFFICE_WEBHOOK_URL'),
    'public_key' => env('LEXOFFICE_WEBHOOK_PUBLIC_KEY'),
];

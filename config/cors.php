<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'handshake-domain'],
    'allowed_methods' => ['*'],
    'allowed_origins' => env('APP_INJECT_DOMAIN', false) ? [env('FRONTEND_URL', 'http://localhost:3000'), env('ID_FRONTEND_URL', 'http://localhost:3000')] : [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
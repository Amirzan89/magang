<?php
$jsonConfig = storage_path('app/database/inject_domain.json');
$injectDomain = '';
if(file_exists($jsonConfig)){
    $injectDomain = json_decode(file_get_contents($jsonConfig))['ID_FRONTEND_URL'];
}
return [
    'paths' => ['*', 'sanctum/csrf-cookie', 'handshake-domain'],
    'allowed_methods' => ['*'],
    // 'allowed_origins' => env('APP_INJECT_DOMAIN', false) && $injectDomain ? [env('FRONTEND_URL', 'http://localhost:3000'), $injectDomain] : [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    // 'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
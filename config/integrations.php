<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integraciones salientes
    |--------------------------------------------------------------------------
    |
    | SJ StatFlow es el maestro de datos de negocio y empuja (push saliente por
    | HTTPS) un subconjunto de empleados a SJ LegalSuite. El transporte se firma
    | con HMAC-SHA256 del body crudo mas un token estatico y un timestamp.
    |
    */

    'legal_suite' => [
        'url' => env('INTEGRATION_LEGALSUITE_URL'),
        'token' => env('INTEGRATION_LEGALSUITE_TOKEN'),
        'secret' => env('INTEGRATION_LEGALSUITE_SECRET'),
        'batch_size' => 500,
        'timeout' => 60,
        // Espera entre reintentos del HTTP client (0 en tests para no dormir).
        'retry_sleep_ms' => 2000,
        'enabled' => filter_var(env('INTEGRATION_LEGALSUITE_ENABLED', false), FILTER_VALIDATE_BOOL),
        // Solo para pruebas locales (Laragon sin SSL). Exige APP_ENV=local
        // y esta bandera explicita; en produccion el transporte siempre es HTTPS.
        'allow_http_local' => filter_var(env('INTEGRATION_LEGALSUITE_ALLOW_HTTP', false), FILTER_VALIDATE_BOOL),
    ],

];

<?php

return [
    /*
    | Firma manuscrita (PNG) por usuario. Disco privado: no se sirve por /storage.
    | Los documentos (p. ej. FO-AD-07) copian un snapshot al autorizar.
    */
    'disk' => env('USER_SIGNATURE_DISK', 'local'),
    'directory' => 'user-signatures',
    'snapshot_directory' => 'purchase-order-signatures',
    'max_kilobytes' => (int) env('USER_SIGNATURE_MAX_KB', 2048),
];

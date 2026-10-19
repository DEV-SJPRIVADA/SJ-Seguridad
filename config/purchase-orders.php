<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FO-AD-07 — Orden de Compra
    |--------------------------------------------------------------------------
    | Metadatos oficiales del formato (Enero 2025 Versión 04). El número de OC
    | visible es el `id` autoincremental de `purchase_orders` (sin prefijo/pad).
    */
    'form_code' => env('PURCHASE_ORDER_FORM_CODE', 'FO-AD-07'),
    'form_version' => env('PURCHASE_ORDER_FORM_VERSION', '04'),
    'form_period' => env('PURCHASE_ORDER_FORM_PERIOD', 'Enero 2025'),
    'report_title' => env('PURCHASE_ORDER_REPORT_TITLE', 'ORDEN DE COMPRA'),
    'company' => env('PURCHASE_ORDER_COMPANY', 'SJ Seguridad'),

    'estados' => [
        'borrador' => 'Borrador',
        'emitida' => 'Emitida',
    ],

    /*
    | Evaluación post-entrega (no se imprime en FO-AD-07).
    | Pesos fijos 25/25/50; calificación entera 1–5.
    */
    'evaluation' => [
        'weights' => [
            'referencias_cantidades' => 25,
            'oportunidad_entrega' => 25,
            'calidad_producto' => 50,
        ],
        'thresholds' => [
            'satisfactoria' => 80,
            'seguimiento' => 60,
        ],
        'labels' => [
            'satisfactoria' => 'Compra satisfactoria',
            'seguimiento' => 'Requiere seguimiento',
            'insatisfactoria' => 'Compra insatisfactoria',
        ],
    ],
];

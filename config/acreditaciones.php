<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pestañas del tablero (labels) — espejo de access.acreditaciones_tabs
    |--------------------------------------------------------------------------
    */
    'tabs' => [
        'dashboard' => 'Dashboard',
        'acreditados' => 'Acreditados',
        'reporte_diario' => 'Reporte Diario',
        'validaciones' => 'Validaciones',
        'export_apo' => 'Export Apo',
        'catalogo' => 'Catálogo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Estados calculados (code BD => label UI)
    |--------------------------------------------------------------------------
    */
    'estados' => [
        'EN_PROCESO' => 'EN PROCESO',
        'DESACREDITADO' => 'DESACREDITADO',
        'POR_VENCER' => 'POR VENCER',
        'ACREDITADO' => 'ACREDITADO',
    ],

    /*
    |--------------------------------------------------------------------------
    | Renovaciones (manual) — code BD => label UI
    |--------------------------------------------------------------------------
    */
    'renovaciones' => [
        'SOLICITADO' => 'Solicitado',
        'RENOVADO' => 'Renovado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ventana POR VENCER (días calendario inclusive desde hoy)
    |--------------------------------------------------------------------------
    */
    'por_vencer_days' => 21,

    /*
    |--------------------------------------------------------------------------
    | Límites de validación / listados
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'observaciones_max' => 5000,
        'datatable_max_length' => 100,
        'bulk_max_ids' => 500,
        'cargo_max' => 255,
        'document_number_max' => 50,
        'full_name_max' => 255,
        'cargo_apo_max' => 255,
        'estado_max' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validaciones (FEAT-038) — corrida efímera en caché
    |--------------------------------------------------------------------------
    */
    'validaciones' => [
        'cache_ttl_seconds' => 5400, // 1.5 h (rango Brief 1–2 h)
        'colas' => [
            'sin_acreditacion' => 'Ficha activa sin acreditación',
            'ausente_reporte' => 'Acreditado ausente del reporte del día',
            'en_proceso_ya_acreditado' => 'EN PROCESO en sistema / ACREDITADO en APO',
            'vencidas' => 'Vencidas / por vencer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Excel (T4) — claves fila 1 / labels fila 2
    |--------------------------------------------------------------------------
    */
    'import' => [
        'columns' => [
            'document_number' => 'CEDULA',
            'full_name' => 'NOMBRE COMPLETO',
            'cargo' => 'CARGO',
            'cargo_apo' => 'CARGO APO',
            'vigencia_acr' => 'VIGEN.ACR',
            'fecha_solicitud' => 'FECHA SOLICITUD',
            'observaciones' => 'OBSERVACIONES',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporte Diario APO (FEAT-037) — snapshot histórico
    |--------------------------------------------------------------------------
    */
    'reporte_diario' => [
        'origenes' => [
            'PROCESO' => 'En proceso',
            'ACREDITADO' => 'Acreditado APO',
        ],
        'headers' => [
            'PROCESO' => [
                'apellido1' => ['apellido1', 'apellido 1', 'primer apellido'],
                'apellido2' => ['apellido2', 'apellido 2', 'segundo apellido'],
                'nombre1' => ['nombre1', 'nombre 1', 'primer nombre'],
                'nombre2' => ['nombre2', 'nombre 2', 'segundo nombre'],
                'document_number' => ['idnum', 'id num', 'cedula', 'documento', 'nro documento', 'numero documento'],
                'cargo' => ['cargo'],
                'estado_apo' => ['estado', 'estado apo'],
            ],
            'ACREDITADO' => [
                'apellido1' => ['apellido1', 'apellido 1', 'primer apellido'],
                'apellido2' => ['apellido2', 'apellido 2', 'segundo apellido'],
                'nombre1' => ['nombre1', 'nombre 1', 'primer nombre'],
                'nombre2' => ['nombre2', 'nombre 2', 'segundo nombre'],
                'document_number' => ['idnum', 'id num', 'cedula', 'documento', 'nro documento', 'numero documento'],
                'cargo' => ['cargo'],
                'vigencia_acr' => [
                    'vigenacr',
                    'vigen acr',
                    'vigen.acr',
                    'vigenciaacr',
                    'vigencia acr',
                    'vigencia',
                    'vigencia acreditacion',
                ],
            ],
        ],
    ],
];

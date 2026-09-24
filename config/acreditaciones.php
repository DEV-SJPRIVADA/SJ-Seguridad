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
];

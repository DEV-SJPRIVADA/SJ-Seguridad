<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Catálogos gestionados (whitelist)
    |--------------------------------------------------------------------------
    */
    'catalog_types' => [
        'estados',
        'tipos-solicitud',
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Excel — headers exactos (T4, opción B replace-por-periodo)
    |--------------------------------------------------------------------------
    */
    'import' => [
        'columns' => [
            'fecha_solicitud' => 'Fecha de solicitud',
            'nombre_apellidos' => 'Nombre y apellidos',
            'cedula' => 'Cédula',
            'correo_electronico' => 'Correo electrónico',
            'solicitud' => 'Solicitud',
            'fecha_respuesta' => 'Fecha de respuesta',
            'estado' => 'Estado',
            'novedad' => 'Novedad',
            'dias_respuesta' => 'Días de respuesta',
        ],
        'memory_limit' => '512M',
        'time_limit' => 300,
        'chunk_size' => 500,
        'max_rows' => 50000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard — bins distribución días (T5)
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'dias_bins' => [
            ['max' => 2, 'label' => '0–2'],
            ['max' => 5, 'label' => '3–5'],
            ['max' => 10, 'label' => '6–10'],
            ['max' => null, 'label' => '11+'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cartas Vacaciones — lote Word (FEAT-043)
    |--------------------------------------------------------------------------
    */
    'cartas_vacaciones' => [
        'max_rows' => 500,
    ],
];

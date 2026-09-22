<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Catálogos administrables desde Selección
    |--------------------------------------------------------------------------
    */
    'managed_catalog_types' => [
        'city',
        'position',
        'eps',
        'afp',
        'blood_type',
        'marital_status',
        'seleccion_solicitud_status',
    ],

    /*
    |--------------------------------------------------------------------------
    | Código canónico SOLICITUD «en proceso» (Dashboard KPI T5)
    |--------------------------------------------------------------------------
    */
    'solicitud_en_proceso_code' => 'EN_PROCESO',

    /*
    |--------------------------------------------------------------------------
    | Seed no destructivo (upsert por catalog_type + code)
    |--------------------------------------------------------------------------
    |
    | @var array<string, list<array{code: string, name: string, sort_order: int}>>
    */
    'catalog_seed_defaults' => [
        'blood_type' => [
            ['code' => 'O+', 'name' => 'O+', 'sort_order' => 1],
            ['code' => 'O-', 'name' => 'O-', 'sort_order' => 2],
            ['code' => 'A+', 'name' => 'A+', 'sort_order' => 3],
            ['code' => 'A-', 'name' => 'A-', 'sort_order' => 4],
            ['code' => 'B+', 'name' => 'B+', 'sort_order' => 5],
            ['code' => 'B-', 'name' => 'B-', 'sort_order' => 6],
            ['code' => 'AB+', 'name' => 'AB+', 'sort_order' => 7],
            ['code' => 'AB-', 'name' => 'AB-', 'sort_order' => 8],
        ],
        'marital_status' => [
            ['code' => 'SOLTERO', 'name' => 'Soltero/a', 'sort_order' => 1],
            ['code' => 'CASADO', 'name' => 'Casado/a', 'sort_order' => 2],
            ['code' => 'UNION_LIBRE', 'name' => 'Unión libre', 'sort_order' => 3],
            ['code' => 'DIVORCIADO', 'name' => 'Divorciado/a', 'sort_order' => 4],
            ['code' => 'VIUDO', 'name' => 'Viudo/a', 'sort_order' => 5],
        ],
        'seleccion_solicitud_status' => [
            ['code' => 'CONTRATADO', 'name' => 'CONTRATADO', 'sort_order' => 1],
            ['code' => 'DXENT_OPERACIONES', 'name' => 'DXENT OPERACIONES', 'sort_order' => 2],
            ['code' => 'DXENT_SELECCION', 'name' => 'DXENT SELECCIÓN', 'sort_order' => 3],
            ['code' => 'DESISTE_DEL_PROCESO', 'name' => 'DESISTE DEL PROCESO', 'sort_order' => 4],
            ['code' => 'DXPSICOFISICO', 'name' => 'DXPSICOFÍSICO', 'sort_order' => 5],
            ['code' => 'DXANT', 'name' => 'DxANT', 'sort_order' => 6],
            ['code' => 'DXEMO_Y_PSICOFISICO', 'name' => 'DxEMO Y PSICOFÍSICO', 'sort_order' => 7],
            ['code' => 'EN_PROCESO', 'name' => 'EN PROCESO', 'sort_order' => 8],
            ['code' => 'DXPOLIGRAFIA', 'name' => 'DXPOLIGRAFÍA', 'sort_order' => 9],
            ['code' => 'DXEMO', 'name' => 'DXEMO', 'sort_order' => 10],
            ['code' => 'EN_RESERVA', 'name' => 'EN RESERVA', 'sort_order' => 11],
        ],
    ],
];

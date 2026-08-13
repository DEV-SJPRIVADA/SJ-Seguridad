<?php

return [
    'enabled' => env('AUDIT_ENABLED', true),
    // Politica de proyecto — sync permanente; no activar cola en Hostinger compartido.
    'queue' => env('AUDIT_QUEUE', false),
    'connection' => env('AUDIT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
    'retention_months' => (int) env('AUDIT_RETENTION_MONTHS', 24),
    'max_json_bytes' => 65536,
    'default_date_range_days' => 30,
    'filter_lookback_days' => 90,

    'modules' => [
        'indicadores' => [
            'label' => 'Indicadores',
            'area' => 'operaciones',
        ],
        'admin' => [
            'label' => 'Administracion',
            'area' => null,
        ],
        'requisitions' => [
            'label' => 'Requisiciones',
            'area' => 'gestion_humana',
        ],
        'commercial' => [
            'label' => 'Comercial',
            'area' => 'comercial',
        ],
        'supplies' => [
            'label' => 'Suministros',
            'area' => null,
        ],
        'purchase_requests' => [
            'label' => 'Compras',
            'area' => 'compras',
        ],
        'quality_documents' => [
            'label' => 'Documentos calidad',
            'area' => 'calidad',
        ],
        'ficha_empleados' => [
            'label' => 'Ficha empleados',
            'area' => 'gestion_humana',
        ],
    ],
];

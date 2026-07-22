<?php

return [
    'base_year' => (int) env('INDICATORS_BASE_YEAR', 2026),
    'future_year_offset' => (int) env('INDICATORS_FUTURE_YEAR_OFFSET', 10),

    'months' => [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ],

    'capture_codes' => [
        'FT-OP-01',
        'FT-OP-02',
        'FT-OP-03',
        'FT-OP-04',
        'FT-OP-05',
        'FT-OP-06',
        'FT-OP-07',
        'FT-OP-08',
        'FT-OP-09',
    ],

    /*
    | Informe de gestion FO-GI-39 (PowerPoint)
    | Plantilla sanitizada: storage/app/templates/operaciones/FO-GI-39-v7.template.pptx
    | Regenerar: python tools/sanitize_pptx_template.py
    */
    'management_report' => [
        'template' => 'templates/operaciones/FO-GI-39-v7.template.pptx',
        'cover' => [
            'slide' => 1,
            'placeholders' => [
                'report_title' => '{{REPORT_TITLE}}',
                'month_name' => '{{MONTH_NAME}}',
                'year' => '{{YEAR}}',
            ],
            'default_title' => 'INFORME DE GESTION DE RIESGOS',
        ],
        'title_labels' => [
            'FT-OP-01' => 'PERSONAL CAPACITADO SJ',
            'FT-OP-02' => 'Servicios No Conformes',
            'FT-OP-03' => 'Siniestralidad',
            'FT-OP-04' => 'Eficacia en la supervision clientes SJ',
            'FT-OP-05' => 'Eficacia en la visita de clientes SJ',
            'FT-OP-06' => 'Estrategias para evitar materializacion',
            'FT-OP-07' => 'Eficacia elaboracion analisis de riesgos',
            'FT-OP-08' => 'Inventario puestos seguridad fisica',
            'FT-OP-09' => 'Inventario de armas',
        ],
        'indicators' => [
            ['code' => 'FT-OP-01', 'slide' => 2, 'chart' => 1],
            ['code' => 'FT-OP-02', 'slide' => 3, 'chart' => 2],
            ['code' => 'FT-OP-03', 'slide' => 4, 'chart' => 3],
            ['code' => 'FT-OP-04', 'slide' => 5, 'chart' => 4],
            ['code' => 'FT-OP-05', 'slide' => 6, 'chart' => 5],
            ['code' => 'FT-OP-06', 'slide' => 7, 'chart' => 6],
            ['code' => 'FT-OP-07', 'slide' => 8, 'chart' => 7],
            ['code' => 'FT-OP-08', 'slide' => 9, 'chart' => 8],
            ['code' => 'FT-OP-09', 'slide' => 10, 'chart' => 9],
        ],
    ],
];

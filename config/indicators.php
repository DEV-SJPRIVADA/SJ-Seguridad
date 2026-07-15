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
];

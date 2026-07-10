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

    'livewire_forms' => [
        'FT-OP-01' => \App\Livewire\Indicadores\FtOp01Form::class,
        'FT-OP-02' => \App\Livewire\Indicadores\FtOp02Form::class,
        'FT-OP-03' => \App\Livewire\Indicadores\FtOp03Form::class,
        'FT-OP-04' => \App\Livewire\Indicadores\FtOp04Form::class,
        'FT-OP-05' => \App\Livewire\Indicadores\FtOp05Form::class,
        'FT-OP-06' => \App\Livewire\Indicadores\FtOp06Form::class,
        'FT-OP-07' => \App\Livewire\Indicadores\FtOp07Form::class,
        'FT-OP-08' => \App\Livewire\Indicadores\FtOp08Form::class,
        'FT-OP-09' => \App\Livewire\Indicadores\FtOp09Form::class,
    ],
];

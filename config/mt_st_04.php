<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zona horaria de negocio (estados / vencimientos)
    |--------------------------------------------------------------------------
    */
    'timezone' => 'America/Bogota',

    /*
    |--------------------------------------------------------------------------
    | Vencimiento = fecha examen + N días
    |--------------------------------------------------------------------------
    */
    'vencimiento_dias' => 364,

    /*
    |--------------------------------------------------------------------------
    | Ventana VENCERA (días calendario inclusive hasta vencimiento)
    |--------------------------------------------------------------------------
    */
    'vencera_dias' => 30,

    /*
    |--------------------------------------------------------------------------
    | Cargos exactos (mb_strtoupper) con ESTADO2 = NO APLICA
    |--------------------------------------------------------------------------
    */
    'no_aplica_cargos' => [
        'GUARDA',
        'OPERADOR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Valores SI/NO (ARMA / APTO)
    |--------------------------------------------------------------------------
    */
    'si_no' => [
        'SI' => 'SI',
        'NO' => 'NO',
    ],

    /*
    |--------------------------------------------------------------------------
    | Estados persistidos
    |--------------------------------------------------------------------------
    */
    'estados' => [
        'VIGENTE' => 'VIGENTE',
        'VENCERA' => 'VENCERA',
        'VENCIDO' => 'VENCIDO',
        'NO_APLICA' => 'NO APLICA',
    ],

    /*
    |--------------------------------------------------------------------------
    | Columnas plantilla / import Excel (T3)
    |--------------------------------------------------------------------------
    */
    'import' => [
        'columns' => [
            'document_number' => 'CEDULA',
            'full_name' => 'NOMBRE COMPLETO',
            'arma' => 'ARMA',
            'fecha_examen_1' => 'FECHA DE EXAMEN',
            'apto' => 'APTO',
            'observaciones_1' => 'OBSERVACIONES',
            'fecha_examen_2' => 'FECHA EXAMEN',
            'observaciones_2' => 'OBSERVACIONES2',
        ],
    ],
];

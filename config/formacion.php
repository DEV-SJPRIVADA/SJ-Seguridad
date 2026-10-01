<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Excel — headers exactos (T3)
    |--------------------------------------------------------------------------
    */
    'import' => [
        'columns' => [
            'numero_id' => 'Número de ID',
            'nombre_completo' => 'Nombre completo',
            'fecha_inicio' => 'Fecha de inicio del curso',
            'nombre_curso' => 'Nombre completo del curso',
            'calificacion' => 'Calificación',
            'categoria' => 'Nombre de la categoría',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export / listado — keys internas (T2)
    |--------------------------------------------------------------------------
    */
    'export' => [
        'columns' => [
            'numero_id',
            'nombre_completo',
            'fecha_inicio',
            'mes',
            'anio',
            'nombre_curso',
            'calificacion',
            'categoria',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard KPIs (T4)
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'categoria_top' => 10,
    ],
];

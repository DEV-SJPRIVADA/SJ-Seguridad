<?php

return [
    'cliente_interno_nombre' => 'SJSEGURIDAD',

    'plazo_dias_habiles' => 10,

    'eficacia' => [
        'abierto' => 'Abierto',
        'en_proceso' => 'En proceso',
        'cerrado' => 'Cerrado',
    ],

    'tipos_cliente' => [
        'interno' => 'Interno',
        'externo' => 'Externo',
    ],

    'recurrencia' => [
        'mes' => 'Mes',
        'trimestre' => 'Trimestre',
        'semestre' => 'Semestre',
        'anio' => 'Año',
    ],

    'export' => [
        'max_rows' => 10000,
    ],

    /*
    | Adjuntos del caso FO-GI-22. Varios por envío; el tope es por lote, no histórico.
    */
    'adjuntos' => [
        'max_files_per_upload' => 10,
        'max_kilobytes' => 10240,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'disk' => 'local',
        'directory' => 'servicio-cliente/fo-gi-22',
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Catálogos fijos MT-GH-04 (V1 — sin pantallas de administración)
    |--------------------------------------------------------------------------
    |
    | Valores canónicos = labels mostrados en UI / Excel.
    |
    | @var array<string, list<string>>
    */
    'catalogos' => [
        'vacaciones_novedad' => [
            'VACACIONES DISF',
            'VACACIONES COMP',
        ],
        'incapacidades_tipo' => [
            'EG',
            'ACC/TRANS',
            'ARL',
            'LP',
            'LM',
        ],
        'retiros_motivo' => [
            'RENUNCIA',
            'SIN JUSTA CAUSA',
            'CON JUSTA CAUSA',
            'TERMINACION DE CONTRATO',
            'FALLECIMIENTO',
            'PERIODO DE PRUEBA',
        ],
        'permisos_novedad' => [
            'LICENCIAS NO REMUN / AUSENCIAS',
            'SANCIONES',
            'PERMISOS REMUNERADOS',
            'DIA DE LA FAMILIA',
            'MATRIMONIO',
            'LUTO',
        ],
    ],

    'retiros_novedad_default' => 'RETIRO',

    /*
    | Adjuntos de la hoja Incapacidades (disco privado; consulta vía ruta autenticada).
    | Se pueden ir sumando lotes; el tope es por envío, no un máximo histórico.
    */
    'incapacidades_adjuntos' => [
        'max_files_per_upload' => 10,
        'max_kilobytes' => 10240,
        'mimes' => ['pdf', 'jpg', 'jpeg'],
        'disk' => 'local',
        'directory' => 'reportes-novedades/incapacidades',
    ],
];

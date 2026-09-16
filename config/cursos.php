<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Documento del curso (T2)
    |--------------------------------------------------------------------------
    */
    'document' => [
        'disk' => 'local',
        'directory' => 'employee-cursos',
        'max_kilobytes' => 10240,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Excel (T3) — claves fila 1 / labels fila 2
    |--------------------------------------------------------------------------
    */
    'import' => [
        'columns' => [
            'cedula' => 'CEDULA',
            'nombre_completo' => 'NOMBRE COMPLETO',
            'tipo_curso' => 'TIPO CURSO',
            'fecha_expedicion' => 'FECHA EXPEDICION',
            'numero_curso_anterior' => 'No.CURSO ANTERIOR (renovacion; opcional)',
            'numero_curso' => 'No.CURSO',
            'estado' => 'ESTADO',
            'observaciones' => 'OBSERVACIONES',
        ],
    ],

    'estados' => [
        '' => '',
        'SOLICITADO' => 'SOLICITADO',
        'ACTUALIZADO' => 'ACTUALIZADO',
    ],
];

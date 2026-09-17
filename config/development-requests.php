<?php

return [
    'attachments' => [
        'max_files' => 10,
        'max_kilobytes' => 10240,
        'mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'],
        'disk' => 'local',
        'directory' => 'development-requests',
    ],

    /*
    | Dias calendario de SLA de analisis (radicado → FT-TIC-03 / en_analisis).
    | Prioridad efectiva: tic_confirmed_priority ?? suggested_priority.
    */
    'sla_analysis_days' => [
        'urgente' => 2,
        'importante' => 5,
        'soporte' => 3,
        'mejora' => 5,
    ],
];

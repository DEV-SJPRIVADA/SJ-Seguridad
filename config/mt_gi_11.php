<?php

/**
 * Configuración del tablero MT-GI-11 (Calidad).
 *
 * `adjuntos` replica los límites del estándar de adjuntos del proyecto.
 * `export.max_rows` protege la memoria del export y del reporte de import.
 */
return [
    'export' => [
        'max_rows' => 10000,
        'chunk_size' => 500,
    ],

    'adjuntos' => [
        'max_files_per_upload' => 10,
        'max_kilobytes' => 10240,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'disk' => 'local',
        'directory' => 'calidad/mt-gi-11',
    ],

    'import' => [
        'chunk_size' => 500,
        'max_rows' => 5000,
    ],
];

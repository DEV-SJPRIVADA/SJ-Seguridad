<?php

return [
    'form_code' => env('PURCHASE_FORM_CODE', 'FO-AD-44'),
    'form_version' => env('PURCHASE_FORM_VERSION', '01'),
    'report_title' => env('PURCHASE_REPORT_TITLE', 'SOLICITUDES DE COMPRAS'),
    'email_approval_link_days' => (int) env('PURCHASE_EMAIL_APPROVAL_LINK_DAYS', 7),
    // URL que abren los directores desde el correo (VPN / servidor real). No use .test
    'public_url' => env('PUBLIC_APP_URL', env('APP_URL', 'http://localhost')),
    'attachments' => [
        'max_files' => 5,
        'max_kilobytes' => 10240,
        'mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'webp'],
        'disk' => 'local',
        'directory' => 'purchase-requests',
    ],

    /*
    | Columnas de plantilla Excel para precargar ítems en Nueva / Editar solicitud.
    | Fila 1 = claves técnicas, fila 2 = etiquetas, datos desde fila 3.
    | La foto no forma parte de la plantilla (se agrega en pantalla).
    */
    'items_import_columns' => [
        'cantidad' => 'Cantidad (obligatorio, entero ≥ 1)',
        'descripcion' => 'Descripción del producto',
        'referencia' => 'Referencia / código',
        'utilizacion' => 'Utilización / uso previsto',
        'ubicacion' => 'Ubicación / sede',
    ],

    'items_import_max_rows' => 200,
];

<?php

return [
    'form_code' => env('PURCHASE_FORM_CODE', 'FO-AD-44'),
    // Metadatos oficiales FO-AD-44 (Marzo 2026 Versión 01)
    'form_version' => env('PURCHASE_FORM_VERSION', '01'),
    'form_period' => env('PURCHASE_FORM_PERIOD', 'Marzo 2026'),
    'report_title' => env('PURCHASE_REPORT_TITLE', 'SOLICITUDES DE COMPRAS'),
    'photo_placeholder' => env('PURCHASE_PHOTO_PLACEHOLDER', 'S/A'),

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

    /*
    | Plantilla / carga masiva de proveedores VINE01.
    | Fila 1 = encabezados oficiales; datos desde fila 2. Solo se importan filas
    | con PROVEEDOR = SI.
    */
    'providers_import_columns' => [
        'IDENTIFICA' => 'Identificación (obligatorio)',
        'NOMBRE' => 'Nombre / razón social (obligatorio)',
        'TELEFONO' => 'Teléfono (solo números)',
        'CORREO_ELE' => 'Correo electrónico',
        'PROVEEDOR' => 'Proveedor (SI/NO). Solo se importan filas con SI',
        'ESTADO' => 'Estado (ACTIVO/INACTIVO)',
        'DIAS_PAGO' => 'Días de pago (opcional, entero 0-3650)',
    ],

    'providers_import_required_columns' => ['IDENTIFICA', 'NOMBRE', 'PROVEEDOR'],

    'providers_import_max_rows' => 20000,

    'providers_import_max_kilobytes' => 20480,
];

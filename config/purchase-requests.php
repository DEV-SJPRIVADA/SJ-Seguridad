<?php

return [
    'form_code' => env('PURCHASE_FORM_CODE', 'FO-AD-44'),
    'form_version' => env('PURCHASE_FORM_VERSION', '01'),
    'report_title' => env('PURCHASE_REPORT_TITLE', 'SOLICITUDES DE COMPRAS'),
    'email_approval_link_days' => (int) env('PURCHASE_EMAIL_APPROVAL_LINK_DAYS', 7),
    // URL que abren los directores desde el correo (VPN / servidor real). No use .test
    'public_url' => env('PUBLIC_APP_URL', env('APP_URL', 'http://localhost')),
];

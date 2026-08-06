<?php

return [
    'email_approval_link_days' => (int) env('REQUISITION_EMAIL_APPROVAL_LINK_DAYS', 7),
    // Usuario registrado en status_logs cuando gerencia aprueba/rechaza desde el correo (guest).
    'email_approval_log_user_id' => env('REQUISITION_EMAIL_APPROVAL_LOG_USER_ID'),
];

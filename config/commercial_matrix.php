<?php

use App\Models\CommercialService;
use App\Support\CommercialDocumentCatalog;

$documentColumns = [];
foreach (CommercialDocumentCatalog::documentFields() as $key => $label) {
    $documentColumns[$key] = $label.' (OK, X, Pendiente, N/A, Incompleto)';
}

return [
    'sheet_name' => 'Matriz comercial',
    'header_key_row' => 1,
    'header_label_row' => 2,
    'data_start_row' => 3,

    'import_columns' => array_merge([
        'nit' => 'NIT (obligatorio)',
        'client_name' => 'Nombre cliente (obligatorio)',
        'phone' => 'Telefono',
        'address' => 'Direccion',
        'city' => 'Ciudad',
        'legal_rep_name' => 'Representante legal',
        'legal_rep_doc' => 'CC representante legal',
        'documentation_expires_on' => 'Vencimiento documentacion (YYYY-MM-DD)',
        'alert_days_before' => 'Dias anticipacion alerta',
        'portfolio' => 'Portafolio (seg_fisica, monitoreo, ocasionales, inactivos)',
        'contract_number' => 'No. contrato',
        'advisor_name' => 'Comercial / asesor',
        'sector' => 'Sector',
        'client_type' => 'Tipo cliente',
        'service_type' => 'Tipo de servicio',
        'service_description' => 'Descripcion del servicio',
        'contact_name' => 'Contacto',
        'contact_role' => 'Cargo contacto',
        'contact_phone' => 'Telefono contacto',
        'contact_email' => 'Correo contacto',
        'contract_start' => 'Fecha inicio contrato (YYYY-MM-DD)',
        'contract_end' => 'Fecha terminacion contrato (YYYY-MM-DD)',
        'duration_months' => 'Duracion (meses)',
        'is_active' => 'Servicio activo (1/0 o Si/No)',
    ], $documentColumns),

    'portfolio_aliases' => [
        'seg. fisica' => CommercialService::PORTFOLIO_SEG_FISICA,
        'seg fisica' => CommercialService::PORTFOLIO_SEG_FISICA,
        'seg_fisica' => CommercialService::PORTFOLIO_SEG_FISICA,
        'monitoreo' => CommercialService::PORTFOLIO_MONITOREO,
        'ocasionales' => CommercialService::PORTFOLIO_OCASIONALES,
        'inactivos' => CommercialService::PORTFOLIO_INACTIVOS,
    ],

    'import_max_file_kb' => 10240,
];

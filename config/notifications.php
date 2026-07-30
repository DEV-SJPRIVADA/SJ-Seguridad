<?php

return [
    'modules' => [
        'requisitions' => 'Requisiciones de personal',
        'comercial' => 'Comercial',
    ],

    'fallback_recipient' => 'desarrollo.tic@sjsp.com.co',

    /*
    | Tipos editables en Administracion → Configuracion de notificaciones (module => slugs).
    | Excluidos aqui: autorizacion gerencia cargo nuevo, correo al solicitante por cambio de estado, etc.
    */
    'admin_configurable' => [
        'requisitions' => [
            'new_requisition',
        ],
        'comercial' => [
            'documentation_expiring',
            'service_contract_expiring',
        ],
    ],
];

<?php

return [
    'system_permissions' => [
        'view.dashboard' => 'Acceder al panel principal',
        'manage.users' => 'Gestionar usuarios, roles y permisos',
        'manage.requisitions' => 'Gestionar requisiciones de personal',
        'manage.requisition.parameters' => 'Administrar parametros de requisiciones',
        'requisitions.tab.dashboard' => 'Requisiciones: Ver Dashboard',
        'requisitions.tab.solicitar' => 'Requisiciones: Solicitar Personal',
        'requisitions.tab.seguimiento' => 'Requisiciones: Seguimiento de Solicitudes',
        'requisitions.tab.gestion' => 'Requisiciones: Gestion de Solicitudes',
        
        // Permisos Granulares de Suministros
        'supply.tab.my_requests' => 'Suministros: Ver Mis Solicitudes',
        'supply.tab.quality' => 'Suministros: Acceso a Aprobacion Insumos',
        'supply.tab.catalog' => 'Suministros: Acceso a Catalogo',

        'manage.supply.catalog' => 'Administrar catalogo de suministros (Full)',
        'approve.supply.quality' => 'Aprobar insumos (permiso completo)',
        'manage.quality.documents' => 'Calidad: Administrar documentos',
    ],

    'areas' => [
        'gestion_humana' => 'Gestion humana',
        'operaciones' => 'Operaciones',
        'programacion' => 'Programacion',
        'juridico' => 'Juridico',
        'comercial' => 'Comercial',
        'calidad' => 'Calidad',
        'remuneraciones' => 'Remuneraciones',
        'facturacion' => 'Facturacion',
        'compras' => 'Compras',
    ],

    'area_actions' => [
        'view' => 'Visualizacion',
        'manage' => 'Funcionalidad',
    ],

    'boards' => [
        'dashboard' => 'Dashboard',
        'requisiciones' => 'Requisiciones',
        'suministros' => 'Suministros',
        'documentos' => 'Documentos',
    ],

    'requisition_tabs' => [
        'dashboard' => 'Dashboard',
        'solicitar' => 'Solicitar',
        'seguimiento' => 'Seguimiento',
        'gestion' => 'Gestion',
        'parametros' => 'Parametros',
    ],

    'supply_tabs' => [
        'mis_solicitudes' => 'Mis Solicitudes',
        'aprobacion_insumos' => 'Aprobacion Insumos',
        'insumos_aprobados' => 'Insumos aprobados',
        'catalogo' => 'Catalogo',
    ],

    'quality_document_tabs' => [
        'biblioteca' => 'Biblioteca',
        'mis_documentos' => 'Mis documentos',
        'administrar' => 'Administrar',
    ],

    'quality_document_types' => [
        'documento_general' => 'Documento general',
        'procedimiento' => 'Procedimiento',
        'formato' => 'Formato',
        'caracterizacion' => 'Caracterizacion',
        'instructivo' => 'Instructivo',
        'programa' => 'Programa',
        'manual' => 'Manual',
        'reglamento' => 'Reglamento',
        'politica' => 'Politica',
        'indicador_gestion' => 'Indicador de gestion',
        'protocolo' => 'Protocolo',
        'perfil_cargo' => 'Perfil de cargo',
        'formulario' => 'Formulario',
        'plan' => 'Plan',
    ],

    'navigation' => [
        'administracion' => [
            'label' => 'Administracion',
            'permission' => 'manage.users',
            'patterns' => ['admin.users.*'],
            'items' => [
                [
                    'label' => 'Usuarios',
                    'route' => 'admin.users.index',
                    'permission' => 'manage.users',
                    'patterns' => ['admin.users.index', 'admin.users.edit'],
                ],
                [
                    'label' => 'Nuevo usuario',
                    'route' => 'admin.users.create',
                    'permission' => 'manage.users',
                    'patterns' => ['admin.users.create'],
                ],
            ],
        ],
    ],
];

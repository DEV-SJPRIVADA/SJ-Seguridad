<?php

return [
    'system_permissions' => [
        'view.dashboard' => 'Acceder al panel principal',
        'manage.users' => 'Gestionar usuarios, roles y permisos',
        'manage.requisitions' => 'Gestionar requisiciones de personal',
        'manage.requisition.parameters' => 'Administrar parametros de requisiciones',
        
        // Permisos Granulares de Suministros
        'supply.tab.my_requests' => 'Suministros: Ver Mis Solicitudes',
        'supply.tab.quality' => 'Suministros: Acceso a Revision Calidad',
        'supply.tab.purchasing' => 'Suministros: Acceso a Gestion Compras',
        'supply.tab.catalog' => 'Suministros: Acceso a Catalogo',

        'manage.supply.catalog' => 'Administrar catalogo de suministros (Full)',
        'approve.supply.quality' => 'Revisar y aprobar suministros (Calidad Full)',
        'manage.supply.purchasing' => 'Gestionar compras y costeo de suministros (Full)',
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
    ],

    'requisition_tabs' => [
        'dashboard' => 'Dashboard',
        'solicitar' => 'Solicitar',
        'gestion' => 'Gestion',
        'parametros' => 'Parametros',
    ],

    'supply_tabs' => [
        'mis_solicitudes' => 'Mis Solicitudes',
        'revision_calidad' => 'Revision Calidad',
        'gestion_compras' => 'Gestion Compras',
        'catalogo' => 'Catalogo',
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

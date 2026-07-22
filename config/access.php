<?php

return [
    // Permisos ocultos en Admin (compatibilidad legacy en codigo; no asignar a usuarios nuevos).
    'admin_hidden_permissions' => [
        'manage.requisitions',
    ],

    'system_permissions' => [
        'view.dashboard' => 'Acceder al panel principal',
        'manage.users' => 'Gestionar usuarios, roles y permisos',
        'manage.requisitions' => 'Gestionar requisiciones de personal (legacy)',
        'manage.requisition.parameters' => 'Administrar parametros de requisiciones',
        'requisitions.tab.dashboard' => 'Requisiciones: Ver Dashboard',
        'requisitions.tab.solicitar' => 'Solicitar requisiciones de personal',
        'requisitions.tab.seguimiento' => 'Requisiciones: Mis requisiciones',
        'requisitions.tab.gestion' => 'Requisiciones: Gestion de Solicitudes',
        
        // Permisos Granulares de Suministros
        'supply.tab.my_requests' => 'Suministros: Ver Mis Solicitudes',
        'supply.tab.quality' => 'Suministros: Acceso a Aprobacion Insumos',
        'supply.tab.catalog' => 'Suministros: Acceso a Catalogo',

        'manage.supply.catalog' => 'Administrar catalogo de suministros (Full)',
        'approve.supply.quality' => 'Aprobar insumos (permiso completo)',
        'manage.quality.documents' => 'Calidad: Administrar documentos',
    ],

    'area_indicador_permissions' => [
        'operaciones' => [
            'operations.view' => 'Indicadores: Ver dashboards',
            'operations.capture' => 'Indicadores: Capturar datos',
            'operations.manage' => 'Indicadores: Administrar (ajustes, consolidado)',
            'operations.export' => 'Indicadores: Exportar PDF y Excel',
        ],
        'comercial' => [
            'comercial.matriz.view' => 'Matriz comercial: Ver clientes y servicios',
            'comercial.matriz.manage' => 'Matriz comercial: Administrar clientes y servicios',
        ],
    ],

    'areas' => [
        'gestion_humana' => 'Gestion humana',
        'operaciones' => 'Operaciones',
        'programacion' => 'Programacion',
        'juridico' => 'Juridico',
        'comercial' => 'Comercial',
        'calidad' => 'Calidad',
        'admin_financiero' => 'Admin y Financiero',
        'compras' => 'Compras',
    ],

    'area_actions' => [
        'view' => 'Visualizacion',
        'manage' => 'Funcionalidad',
    ],

    'boards' => [
        'dashboard' => 'Dashboard',
        'indicadores' => 'Indicadores',
        'requisiciones' => 'Requisiciones',             
        'matriz_clientes' => 'Clientes',
        'servicios_comerciales' => 'Servicios',
        'suministros' => 'Suministros', 
        'documentos' => 'Documentos',
    ],

    'indicador_tabs' => [
        'dashboard' => 'Dashboard',
        'captura' => 'Captura',
        'consolidado' => 'Consolidado',
        'ajustes' => 'Ajustes',     
    ],

    'requisition_tabs' => [
        'dashboard' => 'Dashboard',
        'solicitar' => 'Solicitar',
        'seguimiento' => 'Mis requisiciones',
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

    'admin_ui' => [
        'tabs' => [
            'user' => 'Usuario',
            'capabilities' => 'Que puede hacer',
        ],
        'sections' => [
            'assigned_area' => 'En su area asignada',
            'global' => 'Funcionalidades transversales',
            'other_areas' => 'Activa visualizacion de otras areas',
        ],
        'help' => [
            'area_key' => 'Contexto operativo del usuario. Las acciones de esta seccion solo aplican en el area seleccionada aqui.',
            'capabilities_intro' => 'Asigne permisos transversales una sola vez y, debajo, tableros y funciones exclusivas por area.',
            'assigned_area' => 'Operan unicamente en el area base definida en la pestana Usuario.',
            'global' => 'Acciones que no dependen del area base. Combinelas con tableros visibles en la seccion inferior.',
            'other_areas' => 'Tableros visibles y modulos propios de cada area (GH, Compras, Operaciones, Comercial, Calidad).',
        ],
        'assigned_area_permissions' => [
            'requisitions.tab.solicitar',
            'requisitions.tab.seguimiento',
            'supply.tab.my_requests',
        ],
        'global_groups' => [
            'administration' => [
                'label' => 'Administracion del sistema',
                'permissions' => [
                    'view.dashboard',
                    'manage.users',
                ],
            ],
            'requisitions' => [
                'label' => 'Requisiciones — Gestion humana',
                'permissions' => [
                    'requisitions.tab.gestion',
                    'requisitions.tab.dashboard',
                    'manage.requisition.parameters',
                ],
            ],
            'supplies_calidad' => [
                'label' => 'Suministros — Calidad (aprobacion)',
                'permissions' => [
                    'supply.tab.quality',
                    'approve.supply.quality',
                ],
            ],
            'supplies_compras' => [
                'label' => 'Suministros — Compras (catalogo)',
                'permissions' => [
                    'supply.tab.catalog',
                    'manage.supply.catalog',
                ],
            ],
            'documents' => [
                'label' => 'Documentos de Calidad',
                'permissions' => [
                    'manage.quality.documents',
                ],
                'view_area_access' => true,
            ],
        ],
        'other_areas' => [
            'gestion_humana' => [
                'label' => 'Gestion humana',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros',
                        'permissions' => [
                            'view.board.gestion_humana.requisiciones',
                            'view.board.gestion_humana.dashboard',
                        ],
                    ],
                ],
            ],
            'compras' => [
                'label' => 'Compras',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros de suministros',
                        'permissions' => [
                            'view.board.compras.suministros',
                            'view.board.compras.dashboard',
                        ],
                    ],
                ],
            ],
            'operaciones' => [
                'label' => 'Operaciones',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros',
                        'permissions' => [
                            'view.board.operaciones.dashboard',
                            'view.board.operaciones.indicadores',
                        ],
                    ],
                    'indicadores' => [
                        'label' => 'Indicadores (funciones)',
                        'permissions' => [
                            'operations.view',
                            'operations.capture',
                            'operations.manage',
                            'operations.export',
                        ],
                    ],
                ],
            ],
            'comercial' => [
                'label' => 'Comercial',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros',
                        'permissions' => [
                            'view.board.comercial.dashboard',
                            'view.board.comercial.matriz_clientes',
                            'view.board.comercial.servicios_comerciales',
                        ],
                    ],
                    'matriz' => [
                        'label' => 'Matriz comercial (funciones)',
                        'permissions' => [
                            'comercial.matriz.view',
                            'comercial.matriz.manage',
                        ],
                    ],
                ],
            ],
            'calidad' => [
                'label' => 'Calidad',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros',
                        'permissions' => [
                            'view.board.calidad.dashboard',
                        ],
                    ],
                    'area' => [
                        'label' => 'Acceso al area',
                        'permissions' => [
                            'view.area.calidad',
                            'manage.area.calidad',
                        ],
                    ],
                ],
            ],
        ],
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

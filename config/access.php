<?php

return [
    // Permisos ocultos en Admin (compatibilidad legacy en codigo; no asignar a usuarios nuevos).
    'admin_hidden_permissions' => [
        'manage.requisitions',
    ],

    'system_permissions' => [
        'view.dashboard' => 'Acceder al panel principal',
        'manage.users' => 'Gestionar usuarios, roles y permisos',
        'manage.notifications' => 'Configurar notificaciones por correo (destinatarios y tipos)',
        'system.view.audit' => 'Ver auditoria global del sistema',
        'manage.requisitions' => 'Gestionar requisiciones de personal (legacy)',
        'manage.requisition.parameters' => 'Administrar parametros de requisiciones',
        'manage.commercial.parameters' => 'Administrar parametros comerciales',
        'requisitions.tab.dashboard' => 'Requisiciones: Ver Dashboard',
        'requisitions.tab.solicitar' => 'Solicitar requisiciones de personal',
        'requisitions.tab.seguimiento' => 'Requisiciones: Mis requisiciones',
        'requisitions.tab.gestion' => 'Requisiciones: Gestion de Solicitudes',
        'requisitions.selection_officer' => 'Requisiciones: Actuar como encargado de seleccion',
        'requisitions.approve.management' => 'Requisiciones: Autorizar cargo nuevo (gerencia)',
        'ficha_empleados.view' => 'Ficha empleados: Ver lista de espera y ficha',
        'ficha_empleados.manage' => 'Ficha empleados: Agregar a ficha empleados',

        // Permisos Granulares de Suministros
        'supply.tab.my_requests' => 'Suministros: Ver Mis Solicitudes',
        'supply.tab.quality' => 'Suministros: Acceso a Aprobacion Insumos',
        'supply.tab.catalog' => 'Suministros: Acceso a Catalogo',

        'manage.supply.catalog' => 'Administrar catalogo de suministros (Full)',
        'approve.supply.quality' => 'Aprobar insumos (permiso completo)',
        'manage.quality.documents' => 'Calidad: Administrar documentos',

        // Solicitudes de compra
        'purchase.tab.create' => 'Solicitudes compra: Crear',
        'purchase.tab.my_requests' => 'Solicitudes compra: Mis solicitudes',
        'purchase.tab.approval' => 'Solicitudes compra: Autorizar (director)',
        'purchase.tab.processing' => 'Compras: Bandeja de procesamiento',
    ],

    'area_indicador_permissions' => [
        'operaciones' => [
            'operations.view' => 'Indicadores: Ver dashboards',
            'operations.capture' => 'Indicadores: Capturar datos',
            'operations.capture.delegate' => 'Indicadores: Capturar por suplencia',
            'operations.manage' => 'Indicadores: Administrar (ajustes, consolidado)',
            'operations.export' => 'Indicadores: Exportar PDF y Excel',
        ],
        'comercial' => [
            'comercial.matriz.view' => 'Matriz comercial: Ver clientes y servicios',
            'comercial.matriz.manage' => 'Matriz comercial: Administrar clientes y servicios',
        ],
    ],

    'areas' => [
        'gerencia' => 'Gerencia',
        'gestion_humana' => 'Gestion humana',
        'operaciones' => 'Operaciones',
        'programacion' => 'Programacion',
        'juridico' => 'Juridico',
        'comercial' => 'Comercial',
        'calidad' => 'Calidad',
        'admin_financiero' => 'Admin y Financiero',
        'compras' => 'Compras',
        'Tic' => 'Tic',
    ],

    'area_actions' => [
        'view' => 'Visualizacion',
        'manage' => 'Funcionalidad',
    ],

    'boards' => [
        'dashboard' => 'Dashboard',
        'indicadores' => 'Indicadores',
        'requisiciones' => 'Requisiciones',
        'gestion_clientes' => 'Gestion Clientes',
        'suministros' => 'Suministros',
        'solicitudes_compra' => 'Solicitudes de compra',
        'bandeja_compras' => 'Bandeja compras',
        'documentos' => 'Documentos',
        'ficha_empleados' => 'Ficha empleados',
    ],

    /*
    | Hogares canonicos del sidebar (NavigationResolver + SidebarVisibilityService).
    | Los permisos view.board.{area}.{tablero} en areas que no son hogar solo aplican
    | a solicitantes de esa area; roles transversales deben usar el hogar indicado.
    */
    'board_canonical_areas' => [
        'requisiciones' => [
            'home' => 'gestion_humana',
            'base_area_tab' => true,
        ],
        'suministros' => [
            'home' => 'compras',
            'alt_home' => 'calidad',
            'base_area_tab' => true,
        ],
        'solicitudes_compra' => [
            'home' => 'compras',
            'base_area_tab' => true,
        ],
        'bandeja_compras' => [
            'home' => 'compras',
            'base_area_tab' => false,
        ],
        'documentos' => [
            'home' => null,
            'admin_home' => 'calidad',
            'base_area_tab' => true,
        ],
        'ficha_empleados' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'indicadores' => [
            'home' => 'operaciones',
            'base_area_tab' => false,
        ],
        'gestion_clientes' => [
            'home' => 'comercial',
            'base_area_tab' => false,
        ],
    ],

    'indicador_tabs' => [
        'dashboard' => 'Dashboard',
        'captura' => 'Listado de Indicadores',
        'consolidado' => 'Consolidado',
        'ajustes' => 'Ajustes',
    ],

    'gestion_clientes_tabs' => [
        'clientes' => 'Clientes',
        'servicios' => 'Servicios',
        'parametros' => 'Parametros',
    ],

    'ficha_empleados_tabs' => [
        'empleados' => 'Empleados',
        'catalogos' => 'Catalogos',
    ],

    'comercial_gestion_tab_board_keys' => [
        'matriz_clientes',
        'servicios_comerciales',
    ],

    'requisition_tabs' => [
        'dashboard' => 'Dashboard',
        'solicitar' => 'Solicitar',
        'seguimiento' => 'Mis requisiciones',
        'gestion' => 'Gestion',
        'autorizacion_gerencia' => 'Autorizacion gerencia',
        'parametros' => 'Parametros',
    ],

    'supply_tabs' => [
        'mis_solicitudes' => 'Mis Solicitudes',
        'aprobacion_insumos' => 'Aprobacion Insumos',
        'insumos_aprobados' => 'Insumos aprobados',
        'catalogo' => 'Catalogo',
    ],

    'purchase_tabs' => [
        'nueva' => 'Nueva solicitud',
        'mis_solicitudes' => 'Mis solicitudes',
        'pendientes_aprobacion' => 'Pendientes autorizacion',
        'bandeja_compras' => 'Bandeja compras',
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
            'user' => 'Identidad',
            'capabilities' => 'Acceso y permisos',
            'security' => 'Seguridad',
        ],
        'sections' => [
            'assigned_area' => 'En su area asignada',
            'global' => 'Funcionalidades transversales',
            'other_areas' => 'Activa visualizacion de otras areas',
        ],
        'help' => [
            'area_key' => 'Contexto operativo del usuario. Las acciones de esta seccion solo aplican en el area seleccionada aqui.',
            'capabilities_intro' => 'Asigne permisos transversales una sola vez y, debajo, tableros y funciones exclusivas por area.',
            'assigned_area' => 'Operan unicamente en el area base definida en la pestana Identidad.',
            'global' => 'Acciones que no dependen del area base. Combinelas con tableros visibles en la seccion inferior.',
            'other_areas' => 'Tableros visibles y modulos propios de cada area (GH, Compras, Operaciones, Comercial, Calidad).',
        ],
        'assigned_area_permissions' => [
            'requisitions.tab.solicitar',
            'requisitions.tab.seguimiento',
            'supply.tab.my_requests',
            'purchase.tab.create',
            'purchase.tab.my_requests',
        ],
        'global_groups' => [
            'administration' => [
                'label' => 'Administracion del sistema',
                'permissions' => [
                    'view.dashboard',
                    'manage.users',
                    'manage.notifications',
                    'system.view.audit',
                ],
            ],
            'requisitions' => [
                'label' => 'Requisiciones — Gestion humana',
                'permissions' => [
                    'requisitions.tab.gestion',
                    'requisitions.tab.dashboard',
                    'manage.requisition.parameters',
                    'requisitions.selection_officer',
                    'requisitions.approve.management',
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
            'purchases' => [
                'label' => 'Solicitudes de compra',
                'permissions' => [
                    'purchase.tab.create',
                    'purchase.tab.my_requests',
                    'purchase.tab.approval',
                    'purchase.tab.processing',
                ],
            ],
            'directores' => [
                'label' => 'Directores — Autorizacion compras',
                'permissions' => [
                    'purchase.tab.approval',
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
                            'view.board.gestion_humana.ficha_empleados',
                        ],
                    ],
                    'ficha_empleados' => [
                        'label' => 'Ficha empleados',
                        'permissions' => [
                            'ficha_empleados.view',
                            'ficha_empleados.manage',
                        ],
                    ],
                ],
            ],
            'compras' => [
                'label' => 'Compras',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros',
                        'permissions' => [
                            'view.board.compras.suministros',
                            'view.board.compras.solicitudes_compra',
                            'view.board.compras.bandeja_compras',
                            'view.board.compras.dashboard',
                        ],
                    ],
                    'purchases' => [
                        'label' => 'Solicitudes de compra (funciones)',
                        'permissions' => [
                            'purchase.tab.processing',
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
                            'operations.capture.delegate',
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
                            'view.board.comercial.gestion_clientes',
                        ],
                    ],
                    'matriz' => [
                        'label' => 'Matriz comercial (funciones)',
                        'permissions' => [
                            'comercial.matriz.view',
                            'comercial.matriz.manage',
                            'manage.commercial.parameters',
                            'view.board.comercial.matriz_clientes',
                            'view.board.comercial.servicios_comerciales',
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
            'patterns' => ['admin.users.*', 'admin.notifications.*', 'admin.audit.*'],
            'items' => [
                [
                    'label' => 'Usuarios',
                    'route' => 'admin.users.index',
                    'permission' => 'manage.users',
                    'patterns' => ['admin.users.index', 'admin.users.edit', 'admin.users.create'],
                ],
                [
                    'label' => 'Configuracion de notificaciones',
                    'route' => 'admin.notifications.index',
                    'permission' => 'manage.notifications',
                    'patterns' => ['admin.notifications.*'],
                ],
                [
                    'label' => 'Auditoria del sistema',
                    'route' => 'admin.audit.index',
                    'permission' => 'system.view.audit',
                    'patterns' => ['admin.audit.*'],
                ],
            ],
        ],
    ],
];

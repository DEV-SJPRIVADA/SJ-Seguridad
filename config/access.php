<?php

return [
    // Permisos ocultos en Admin (compatibilidad legacy en codigo; no asignar a usuarios nuevos).
    'admin_hidden_permissions' => [
        'manage.requisitions',
        'manage.commercial.parameters',
        'comercial.matriz.view',
        'comercial.matriz.manage',
        'view.board.comercial.matriz_clientes',
        'view.board.comercial.servicios_comerciales',
    ],

    'system_permissions' => [
        'view.dashboard' => 'Acceder al panel principal',
        'manage.users' => 'Gestionar usuarios, roles y permisos',
        'manage.notifications' => 'Configurar notificaciones por correo (destinatarios y tipos)',
        'system.view.audit' => 'Ver auditoría global del sistema',
        'manage.requisitions' => 'Gestionar requisiciones de personal (legacy)',
        'manage.requisition.parameters' => 'Administrar catálogos de requisiciones',
        'manage.commercial.parameters' => 'Administrar catálogos comerciales (legacy)',
        'comercial.matriz.view' => 'Matriz comercial: Ver clientes y servicios (legacy)',
        'comercial.matriz.manage' => 'Matriz comercial: Administrar clientes y servicios (legacy)',
        'requisitions.tab.dashboard' => 'Requisiciones: Ver Dashboard',
        'requisitions.tab.solicitar' => 'Solicitar requisiciones de personal',
        'requisitions.tab.seguimiento' => 'Requisiciones: Mis requisiciones',
        'requisitions.tab.gestion' => 'Requisiciones: Gestión de Solicitudes',
        'requisitions.selection_officer' => 'Requisiciones: Actuar como encargado de selección',
        'requisitions.approve.management' => 'Requisiciones: Autorizar cargo nuevo (gerencia)',
        'ficha_empleados.view' => 'Ficha empleados: Ver lista de espera y ficha',
        'ficha_empleados.manage' => 'Ficha empleados: Agregar a ficha empleados',
        'ficha_empleados.terminate' => 'Ficha empleados: Registrar desvinculación de empleados',
        'archivo.view' => 'Archivo: Ver ubicación documental de empleados',
        'archivo.manage' => 'Archivo: Gestionar estantes y cajas',
        'plantillas_word.view' => 'Plantillas Word: Ver tipos y plantillas',
        'plantillas_word.manage' => 'Plantillas Word: Administrar tipos y plantillas',
        'cartas_notificacion.edit' => 'Cartas Notificación: Generar',
        'inasistencias.view' => 'Inasistencias: Ver histórico y export',
        'inasistencias.edit' => 'Inasistencias: Generar',
        'desvinculaciones.view' => 'Desvinculaciones: Acceder al tablero (Dashboard, Masivos y Seguimientos)',
        'desvinculaciones.masivos' => 'Desvinculaciones: Ejecutar desvinculaciones masivas',
        'desvinculaciones.seguimientos.edit' => 'Desvinculaciones: Editar checks y fecha entregado nómina',
        'cursos.view' => 'Cursos: Ver listado, filtros y export Excel',
        'cursos.edit' => 'Cursos: CRUD registros, plantilla, import y catálogo',
        'formacion.view' => 'Formación: Ver Dashboard, listado, filtros y export Excel',
        'formacion.edit' => 'Formación: Descargar plantilla e importar (replace-all)',
        'mt_st_04.view' => 'MT-ST-04: Ver',
        'mt_st_04.edit' => 'MT-ST-04: Editar',
        'seleccion.view' => 'Selección: Ver dashboard, listados y export Excel',
        'seleccion.edit' => 'Selección: CRUD Ingreso, Examen ocupacional y Catálogos',
        'cliente_interno.solicitudes.view' => 'Cliente interno: Ver solicitudes',
        'cliente_interno.solicitudes.edit' => 'Cliente interno: Editar solicitudes',
        'cliente_interno.cartas_vacaciones.view' => 'Cliente interno: Ver Cartas Vacaciones',
        'cliente_interno.cartas_vacaciones.edit' => 'Cliente interno: Generar Cartas Vacaciones',
        'cliente_interno.parameters.edit' => 'Cliente interno: Catálogos',
        'servicio_cliente.fo_gi_22.view' => 'FO-GI-22: Ver',
        'servicio_cliente.fo_gi_22.edit' => 'FO-GI-22: Editar',
        'servicio_cliente.parameters.edit' => 'Servicio al cliente: Catálogos',
        'acreditaciones.view' => 'Acreditaciones: Ver shell, Acreditados, export y placeholders',
        'acreditaciones.edit' => 'Acreditaciones: CRUD Acreditados, import y Catálogo',
        'reportes_novedades.vacaciones.view' => 'Vacaciones: Ver',
        'reportes_novedades.vacaciones.edit' => 'Vacaciones: Editar',
        'reportes_novedades.vacaciones.review' => 'Vacaciones: Revisar (Nómina)',
        'reportes_novedades.incapacidades.view' => 'Incapacidades: Ver',
        'reportes_novedades.incapacidades.edit' => 'Incapacidades: Editar',
        'reportes_novedades.incapacidades.review' => 'Incapacidades: Revisar (Nómina)',
        'reportes_novedades.retiros.view' => 'Retiros: Ver',
        'reportes_novedades.retiros.edit' => 'Retiros: Editar',
        'reportes_novedades.retiros.review' => 'Retiros: Revisar (Nómina)',
        'reportes_novedades.permisos.view' => 'Permisos: Ver',
        'reportes_novedades.permisos.edit' => 'Permisos: Editar',
        'reportes_novedades.permisos.review' => 'Permisos: Revisar (Nómina)',

        // Permisos Granulares de Suministros
        'supply.tab.my_requests' => 'Suministros: Ver Mis Solicitudes',
        'supply.tab.quality' => 'Suministros: Acceso a Aprobación Insumos',
        'supply.tab.catalog' => 'Suministros: Acceso a Catálogo',

        'manage.supply.catalog' => 'Administrar catálogo de suministros (Full)',
        'approve.supply.quality' => 'Aprobar insumos (permiso completo)',
        'manage.quality.documents' => 'Calidad: Administrar documentos',

        // Solicitudes de compra
        'purchase.tab.create' => 'Solicitudes compra: Crear',
        'purchase.tab.my_requests' => 'Solicitudes compra: Mis solicitudes',
        'purchase.tab.approval' => 'Solicitudes compra: Autorizar (director)',
        'purchase.tab.processing' => 'Compras: Bandeja de procesamiento',
        'purchase.providers.view' => 'Proveedores: Ver',
        'purchase.providers.edit' => 'Proveedores: Editar',

        // Solicitudes de desarrollo (TIC)
        'devreq.tab.create' => 'Solicitudes desarrollo: Crear',
        'devreq.tab.my_requests' => 'Solicitudes desarrollo: Mis solicitudes',
        'devreq.tab.leader_approval' => 'Solicitudes desarrollo: Aprobación líder',
        'devreq.tab.tic_queue' => 'Solicitudes desarrollo: Bandeja TIC',
        'devreq.tab.view' => 'Solicitudes desarrollo: Consulta',
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
            'comercial.clients.view' => 'Clientes: Ver',
            'comercial.clients.edit' => 'Clientes: Editar',
            'comercial.services.view' => 'Servicios: Ver',
            'comercial.services.edit' => 'Servicios: Editar',
            'comercial.parameters.edit' => 'Catálogos: Editar',
        ],
    ],

    /*
    | Explicación mostrada debajo de cada permiso en Admin → Usuarios → Acceso y permisos.
    | Los permisos de tablero (`view.board.*`) y de área (`view.area.*` / `manage.area.*`)
    | se explican automáticamente en UserPermissionFormBuilder.
    */
    'permission_help' => [
        'view.dashboard' => 'Abre el panel principal con el resumen de indicadores y los accesos rápidos del sistema.',
        'manage.users' => 'Permite crear, editar, activar o desactivar usuarios, y asignar roles y permisos.',
        'manage.notifications' => 'Permite definir quién recibe los correos del sistema y qué tipos de notificación se envían.',
        'system.view.audit' => 'Permite consultar el registro global de acciones y cambios realizados en el sistema.',

        'manage.requisition.parameters' => 'Permite crear y editar los catálogos que usan las requisiciones, como cargos y ciudades.',
        'requisitions.tab.dashboard' => 'Muestra el tablero de indicadores de requisiciones de personal.',
        'requisitions.tab.solicitar' => 'Permite crear nuevas requisiciones de personal.',
        'requisitions.tab.seguimiento' => 'Permite consultar y seguir las requisiciones creadas por el usuario.',
        'requisitions.tab.gestion' => 'Permite gestionar las requisiciones del área: revisarlas, actualizarlas y darles seguimiento.',
        'requisitions.selection_officer' => 'Permite actuar como encargado de selección dentro del proceso de requisiciones.',
        'requisitions.approve.management' => 'Permite autorizar la creación de cargos nuevos desde Gerencia.',

        'ficha_empleados.view' => 'Permite ver la lista de espera y la ficha de los empleados.',
        'ficha_empleados.manage' => 'Permite agregar empleados a la ficha y actualizar sus datos.',
        'ficha_empleados.terminate' => 'Permite registrar la desvinculación de empleados en la ficha.',
        'archivo.view' => 'Permite consultar la ubicación documental de los empleados, con estante y caja.',
        'archivo.manage' => 'Permite administrar los estantes y cajas del archivo documental.',
        'plantillas_word.view' => 'Permite ver los tipos de plantilla y descargar las plantillas Word.',
        'plantillas_word.manage' => 'Permite crear, editar y organizar los tipos y plantillas Word.',
        'cartas_notificacion.edit' => 'Permite generar cartas de notificación.',
        'inasistencias.view' => 'Permite consultar el histórico de inasistencias y exportarlo.',
        'inasistencias.edit' => 'Permite registrar y generar inasistencias.',

        'desvinculaciones.view' => 'Permite acceder a los tableros de desvinculaciones: Dashboard, Masivos y Seguimientos.',
        'desvinculaciones.masivos' => 'Permite ejecutar desvinculaciones masivas.',
        'desvinculaciones.seguimientos.edit' => 'Permite editar los checks y la fecha de entrega a nómina en los seguimientos.',
        'cursos.view' => 'Permite ver el listado de cursos, aplicar filtros y exportar a Excel.',
        'cursos.edit' => 'Permite crear, editar y eliminar registros de cursos, usar la plantilla o importación y administrar el catálogo.',
        'formacion.view' => 'Permite ver el dashboard y el listado de formación, aplicar filtros y exportar a Excel.',
        'formacion.edit' => 'Permite descargar la plantilla e importar la formación con reemplazo total.',
        'mt_st_04.view' => 'Permite consultar el tablero MT-ST-04.',
        'mt_st_04.edit' => 'Permite editar la información del tablero MT-ST-04.',
        'seleccion.view' => 'Permite ver el dashboard, los listados y exportar Selección a Excel.',
        'seleccion.edit' => 'Permite administrar Ingresos, Exámenes ocupacionales y Catálogos de Selección.',

        'cliente_interno.solicitudes.view' => 'Permite ver las solicitudes de cliente interno.',
        'cliente_interno.solicitudes.edit' => 'Permite crear y editar solicitudes de cliente interno.',
        'cliente_interno.cartas_vacaciones.view' => 'Permite ver las cartas de vacaciones de cliente interno.',
        'cliente_interno.cartas_vacaciones.edit' => 'Permite generar cartas de vacaciones.',
        'cliente_interno.parameters.edit' => 'Permite administrar los catálogos de cliente interno.',
        'servicio_cliente.fo_gi_22.view' => 'Permite ver FO-GI-22, el dashboard, filtrar y exportar a Excel.',
        'servicio_cliente.fo_gi_22.edit' => 'Permite crear, editar y eliminar casos, observaciones y adjuntos.',
        'servicio_cliente.parameters.edit' => 'Permite administrar tipología, fuente, responsable y categoría.',
        'acreditaciones.view' => 'Permite ver Acreditados y exportar la información, además de mostrar los módulos del área.',
        'acreditaciones.edit' => 'Permite crear, editar y eliminar acreditados, importar información y administrar el catálogo.',

        'reportes_novedades.vacaciones.view' => 'Permite ver los reportes de vacaciones.',
        'reportes_novedades.vacaciones.edit' => 'Permite registrar y editar vacaciones.',
        'reportes_novedades.vacaciones.review' => 'Permite revisar y aprobar vacaciones desde Nómina.',
        'reportes_novedades.incapacidades.view' => 'Permite ver los reportes de incapacidades.',
        'reportes_novedades.incapacidades.edit' => 'Permite registrar y editar incapacidades.',
        'reportes_novedades.incapacidades.review' => 'Permite revisar y aprobar incapacidades desde Nómina.',
        'reportes_novedades.retiros.view' => 'Permite ver los reportes de retiros.',
        'reportes_novedades.retiros.edit' => 'Permite registrar y editar retiros.',
        'reportes_novedades.retiros.review' => 'Permite revisar y aprobar retiros desde Nómina.',
        'reportes_novedades.permisos.view' => 'Permite ver los reportes de permisos.',
        'reportes_novedades.permisos.edit' => 'Permite registrar y editar permisos.',
        'reportes_novedades.permisos.review' => 'Permite revisar y aprobar permisos desde Nómina.',

        'supply.tab.my_requests' => 'Permite crear y consultar las solicitudes de insumos propias.',
        'supply.tab.quality' => 'Permite acceder a la aprobación de insumos y revisar solicitudes de todas las áreas.',
        'supply.tab.catalog' => 'Permite acceder al catálogo de insumos.',
        'manage.supply.catalog' => 'Permite crear y editar el catálogo completo de insumos.',
        'approve.supply.quality' => 'Permite aprobar, devolver y ajustar cantidades de las solicitudes de insumos.',
        'manage.quality.documents' => 'Permite administrar los documentos de Calidad: biblioteca, tipos y publicación.',

        'purchase.tab.create' => 'Permite crear nuevas solicitudes de compra.',
        'purchase.tab.my_requests' => 'Permite consultar y dar seguimiento a las solicitudes de compra propias.',
        'purchase.tab.approval' => 'Permite autorizar o rechazar solicitudes y órdenes de compra cuando el usuario es el aprobador asignado.',
        'purchase.tab.processing' => 'Permite acceder a la bandeja de Compras: procesar pedidos, emitir órdenes y cerrar saldos.',
        'purchase.providers.view' => 'Permite ver el catálogo de proveedores.',
        'purchase.providers.edit' => 'Permite crear, editar e importar proveedores, y administrar las tasas de IVA.',

        'devreq.tab.create' => 'Permite crear solicitudes de desarrollo.',
        'devreq.tab.my_requests' => 'Permite consultar las solicitudes de desarrollo propias.',
        'devreq.tab.leader_approval' => 'Permite aprobar solicitudes de desarrollo como líder.',
        'devreq.tab.tic_queue' => 'Permite gestionar la bandeja de solicitudes de desarrollo de TIC.',
        'devreq.tab.view' => 'Permite consultar solicitudes de desarrollo en modo solo lectura.',

        'operations.view' => 'Permite ver los dashboards de indicadores de Operaciones.',
        'operations.capture' => 'Permite capturar los datos de los indicadores de Operaciones.',
        'operations.capture.delegate' => 'Permite capturar indicadores en nombre de otro usuario por suplencia.',
        'operations.manage' => 'Permite administrar los ajustes y el consolidado de indicadores.',
        'operations.export' => 'Permite exportar los indicadores a PDF y Excel.',
        'comercial.clients.view' => 'Permite ver los clientes en Gestión Clientes.',
        'comercial.clients.edit' => 'Permite crear y editar clientes.',
        'comercial.services.view' => 'Permite ver los servicios en Gestión Clientes.',
        'comercial.services.edit' => 'Permite crear y editar servicios.',
        'comercial.parameters.edit' => 'Permite administrar los catálogos comerciales.',
    ],

    'areas' => [
        'gerencia' => 'Gerencia',
        'gestion_humana' => 'Gestión Humana',
        'juridico' => 'Jurídico',
        'operaciones' => 'Operaciones',
        'programacion' => 'Plan. y Programación',
        'comercial' => 'Comercial',
        'calidad' => 'Calidad',
        'admin_financiero' => 'Admin y Financiero',
        'compras' => 'Compras',
        'tic' => 'TIC',
    ],

    'area_actions' => [
        'view' => 'Visualización',
        'manage' => 'Funcionalidad',
    ],

    'boards' => [
        'dashboard' => 'Dashboard',
        'indicadores' => 'Indicadores',
        'requisiciones' => 'Requisiciones',
        'gestion_clientes' => 'Gestión Clientes',
        'suministros' => 'Suministros',
        'solicitudes_compra' => 'Solicitudes de compra',
        'bandeja_compras' => 'Pedidos',
        'proveedores' => 'Catálogos',
        'ordenes_compra' => 'Órdenes',
        'solicitudes_desarrollo' => 'Solicitudes desarrollo',
        'ficha_empleados' => 'Ficha empleados',
        'desvinculaciones' => 'Desvinculaciones',
        'cursos' => 'Cursos',
        'formacion' => 'Formación',
        'mt_st_04' => 'MT-ST-04',
        'seleccion' => 'Selección',
        'cliente_interno' => 'Cliente interno',
        'acreditaciones' => 'Acreditaciones',
        'reportes_novedades' => 'MT-GH-04 Novedades',
        'servicio_cliente' => 'Servicio al cliente',
        'archivo' => 'Archivo',
        'plantillas_word' => 'Plantillas Word',
        'cartas_notificacion' => 'Cartas Notificación',
        'inasistencias' => 'Inasistencias',
        'documentos' => 'Biblioteca Calidad',
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
        'proveedores' => [
            'home' => 'compras',
            'base_area_tab' => false,
        ],
        'ordenes_compra' => [
            'home' => 'compras',
            'base_area_tab' => false,
        ],
        'solicitudes_desarrollo' => [
            'home' => 'tic',
            'base_area_tab' => true,
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
        'archivo' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'plantillas_word' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'cartas_notificacion' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'inasistencias' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'desvinculaciones' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'cursos' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'formacion' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'mt_st_04' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'seleccion' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'cliente_interno' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'acreditaciones' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'reportes_novedades' => [
            'home' => 'gestion_humana',
            'base_area_tab' => false,
        ],
        'servicio_cliente' => [
            'home' => 'calidad',
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
        'parametros' => 'Catalogos',
    ],

    'ficha_empleados_tabs' => [
        'empleados' => 'Empleados',
        'catalogos' => 'Catalogos',
    ],

    'desvinculaciones_tabs' => [
        'dashboard' => 'Dashboard',
        'masivos' => 'Masivos',
        'seguimientos' => 'Seguimientos',
    ],

    'cursos_tabs' => [
        'dashboard' => 'Dashboard',
        'registros' => 'Cursos',
        'validaciones' => 'Validaciones',
        'catalogo' => 'Catálogo',
    ],

    'formacion_tabs' => [
        'dashboard' => 'Dashboard',
        'formaciones' => 'Formaciones',
    ],

    'mt_st_04_tabs' => [
        'dashboard' => 'Dashboard',
        'matriz' => 'Matriz',
        'validaciones' => 'Validaciones',
    ],

    'seleccion_tabs' => [
        'dashboard' => 'Dashboard',
        'ingresos' => 'Ingreso',
        'examenes' => 'Examen ocupacional',
        'catalogos' => 'Catálogos',
    ],

    'servicio_cliente_tabs' => [
        'dashboard' => 'Dashboard',
        'fo_gi_22' => 'FO-GI-22',
        'catalogos' => 'Catálogos',
    ],

    'cliente_interno_tabs' => [
        'dashboard' => 'Dashboard',
        'solicitudes' => 'Solicitudes',
        'cartas_vacaciones' => 'Cartas Vacaciones',
        'catalogos' => 'Catálogos',
    ],

    'acreditaciones_tabs' => [
        'dashboard' => 'Dashboard',
        'acreditados' => 'Acreditados',
        'reporte_diario' => 'Reporte Diario',
        'validaciones' => 'Validaciones',
        'export_apo' => 'Export Apo',
        'catalogo' => 'Catálogo',
    ],

    'inasistencias_tabs' => [
        'generar' => 'Generar',
        'historico' => 'Histórico',
    ],

    'reportes_novedades_tabs' => [
        'vacaciones' => 'Vacaciones',
        'incapacidades' => 'Incapacidades',
        'retiros' => 'Retiros',
        'permisos' => 'Permisos',
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
        'parametros' => 'Catalogos',
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
        'pendientes_aprobacion' => 'Autorizaciones compras',
        'bandeja_compras' => 'Bandeja compras',
    ],

    'devreq_tabs' => [
        'nueva' => 'Nueva solicitud',
        'mis_solicitudes' => 'Mis solicitudes',
        'aprobacion_lider' => 'Aprobacion lider',
        'bandeja_tic' => 'Bandeja TIC',
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
        'politica' => 'Política',
        'indicador_gestion' => 'Indicador de gestión',
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
            'assigned_area' => 'Solicitar en su área',
            'global' => 'Funcionalidades transversales',
            'other_areas' => 'Activa visualización de otras áreas',
        ],
        'help' => [
            'area_key' => 'Contexto operativo del usuario. Las acciones de esta sección solo aplican en el área seleccionada aquí.',
            'capabilities_intro' => 'Asigne permisos transversales una sola vez y, debajo, tableros y funciones exclusivas por área.',
            'assigned_area' => 'Pedir personal, insumos o compras en el área de Identidad. Los módulos del departamento (ficha, archivo, indicadores) están en cada área.',
            'global' => 'Acciones que no dependen del área base. Combínelas con tableros visibles en la sección inferior.',
            'other_areas' => 'Tableros visibles y módulos propios de cada área (GH, Compras, Operaciones, Comercial, Calidad).',
        ],
        'assigned_area_permissions' => [
            'requisitions.tab.solicitar',
            'requisitions.tab.seguimiento',
            'supply.tab.my_requests',
            'purchase.tab.create',
            'purchase.tab.my_requests',
            'devreq.tab.create',
            'devreq.tab.my_requests',
        ],
        'global_groups' => [
            'administration' => [
                'label' => 'Administración del sistema',
                'permissions' => [
                    'view.dashboard',
                    'manage.users',
                    'manage.notifications',
                    'system.view.audit',
                ],
            ],
            'requisitions' => [
                'label' => 'Requisiciones — Gestión Humana',
                'permissions' => [
                    'requisitions.tab.gestion',
                    'requisitions.tab.dashboard',
                    'manage.requisition.parameters',
                    'requisitions.selection_officer',
                    'requisitions.approve.management',
                ],
            ],
            'supplies_calidad' => [
                'label' => 'Suministros — Calidad (aprobación)',
                'permissions' => [
                    'supply.tab.quality',
                    'approve.supply.quality',
                ],
            ],
            'supplies_compras' => [
                'label' => 'Suministros — Compras (catálogo)',
                'permissions' => [
                    'supply.tab.catalog',
                    'manage.supply.catalog',
                ],
            ],
            'purchases' => [
                'label' => 'Solicitudes de compra',
                'permissions' => [
                    'purchase.tab.approval',
                ],
            ],
            'directores' => [
                'label' => 'Directores — Autorización compras',
                'permissions' => [
                    'purchase.tab.approval',
                ],
            ],
            'development_requests' => [
                'label' => 'Solicitudes de desarrollo — Aprobación líder',
                'permissions' => [
                    'devreq.tab.leader_approval',
                ],
            ],
            'documents' => [
                'label' => 'Documentos de Calidad',
                'permissions' => [
                    'manage.quality.documents',
                ],
            ],
        ],
        'other_areas' => [
            'gestion_humana' => [
                'label' => 'Gestión Humana',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros',
                        'permissions' => [
                            'view.board.gestion_humana.requisiciones',
                            'view.board.gestion_humana.dashboard',
                            'view.board.gestion_humana.ficha_empleados',
                            'view.board.gestion_humana.archivo',
                            'view.board.gestion_humana.plantillas_word',
                            'view.board.gestion_humana.cartas_notificacion',
                            'view.board.gestion_humana.inasistencias',
                            'view.board.gestion_humana.desvinculaciones',
                            'view.board.gestion_humana.cursos',
                            'view.board.gestion_humana.formacion',
                            'view.board.gestion_humana.mt_st_04',
                            'view.board.gestion_humana.seleccion',
                            'view.board.gestion_humana.cliente_interno',
                            'view.board.gestion_humana.acreditaciones',
                            'view.board.gestion_humana.reportes_novedades',
                        ],
                    ],
                    'ficha_empleados' => [
                        'label' => 'Ficha empleados',
                        'permissions' => [
                            'ficha_empleados.view',
                            'ficha_empleados.manage',
                            'ficha_empleados.terminate',
                        ],
                    ],
                    'archivo' => [
                        'label' => 'Archivo',
                        'permissions' => [
                            'archivo.view',
                            'archivo.manage',
                        ],
                    ],
                    'plantillas_word' => [
                        'label' => 'Plantillas Word',
                        'permissions' => [
                            'plantillas_word.view',
                            'plantillas_word.manage',
                        ],
                    ],
                    'cartas_notificacion' => [
                        'label' => 'Cartas Notificación',
                        'permissions' => [
                            'cartas_notificacion.edit',
                        ],
                    ],
                    'inasistencias' => [
                        'label' => 'Inasistencias',
                        'permissions' => [
                            'inasistencias.view',
                            'inasistencias.edit',
                        ],
                    ],
                    'desvinculaciones' => [
                        'label' => 'Desvinculaciones',
                        'permissions' => [
                            'desvinculaciones.view',
                            'desvinculaciones.masivos',
                            'desvinculaciones.seguimientos.edit',
                        ],
                    ],
                    'cursos' => [
                        'label' => 'Cursos',
                        'permissions' => [
                            'cursos.view',
                            'cursos.edit',
                        ],
                    ],
                    'formacion' => [
                        'label' => 'Formación',
                        'permissions' => [
                            'formacion.view',
                            'formacion.edit',
                        ],
                    ],
                    'mt_st_04' => [
                        'label' => 'MT-ST-04',
                        'permissions' => [
                            'mt_st_04.view',
                            'mt_st_04.edit',
                        ],
                    ],
                    'seleccion' => [
                        'label' => 'Selección',
                        'permissions' => [
                            'seleccion.view',
                            'seleccion.edit',
                        ],
                    ],
                    'cliente_interno' => [
                        'label' => 'Cliente interno',
                        'permissions' => [
                            'cliente_interno.solicitudes.view',
                            'cliente_interno.solicitudes.edit',
                            'cliente_interno.cartas_vacaciones.view',
                            'cliente_interno.cartas_vacaciones.edit',
                            'cliente_interno.parameters.edit',
                        ],
                    ],
                    'acreditaciones' => [
                        'label' => 'Acreditaciones',
                        'permissions' => [
                            'acreditaciones.view',
                            'acreditaciones.edit',
                        ],
                    ],
                    'reportes_novedades' => [
                        'label' => 'MT-GH-04 Novedades',
                        'permissions' => [
                            'reportes_novedades.vacaciones.view',
                            'reportes_novedades.vacaciones.edit',
                            'reportes_novedades.vacaciones.review',
                            'reportes_novedades.incapacidades.view',
                            'reportes_novedades.incapacidades.edit',
                            'reportes_novedades.incapacidades.review',
                            'reportes_novedades.retiros.view',
                            'reportes_novedades.retiros.edit',
                            'reportes_novedades.retiros.review',
                            'reportes_novedades.permisos.view',
                            'reportes_novedades.permisos.edit',
                            'reportes_novedades.permisos.review',
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
                    'providers' => [
                        'label' => 'Proveedores (catálogo)',
                        'permissions' => [
                            'purchase.providers.view',
                            'purchase.providers.edit',
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
                        'label' => 'Gestión Clientes (funciones)',
                        'permissions' => [
                            'comercial.clients.view',
                            'comercial.clients.edit',
                            'comercial.services.view',
                            'comercial.services.edit',
                            'comercial.parameters.edit',
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
                            'view.board.calidad.servicio_cliente',
                        ],
                    ],
                    'servicio_cliente' => [
                        'label' => 'Servicio al cliente (funciones)',
                        'permissions' => [
                            'servicio_cliente.fo_gi_22.view',
                            'servicio_cliente.fo_gi_22.edit',
                            'servicio_cliente.parameters.edit',
                        ],
                    ],
                    'area' => [
                        'label' => 'Acceso al área',
                        'permissions' => [
                            'view.area.calidad',
                            'manage.area.calidad',
                        ],
                    ],
                ],
            ],
            'tic' => [
                'label' => 'TIC',
                'subgroups' => [
                    'boards' => [
                        'label' => 'Ver tableros',
                        'permissions' => [
                            'view.board.tic.solicitudes_desarrollo',
                            'view.board.tic.dashboard',
                        ],
                    ],
                    'development_requests' => [
                        'label' => 'Solicitudes de desarrollo (funciones)',
                        'permissions' => [
                            'devreq.tab.tic_queue',
                            'devreq.tab.view',
                            'devreq.tab.leader_approval',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'navigation' => [
        'administracion' => [
            'label' => 'Administración',
            'patterns' => ['admin.users.*', 'admin.notifications.*', 'admin.audit.*'],
            'items' => [
                [
                    'label' => 'Usuarios',
                    'route' => 'admin.users.index',
                    'permission' => 'manage.users',
                    'patterns' => ['admin.users.index', 'admin.users.edit', 'admin.users.create'],
                ],
                [
                    'label' => 'Configuración de notificaciones',
                    'route' => 'admin.notifications.index',
                    'permission' => 'manage.notifications',
                    'patterns' => ['admin.notifications.*'],
                ],
                [
                    'label' => 'Auditoría del sistema',
                    'route' => 'admin.audit.index',
                    'permission' => 'system.view.audit',
                    'patterns' => ['admin.audit.*'],
                ],
            ],
        ],
    ],
];

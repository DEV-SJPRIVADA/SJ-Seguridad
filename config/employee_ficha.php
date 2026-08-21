<?php

return [
    'employment_status' => [
        'activo' => 'Activo',
        'desvinculado' => 'Desvinculado',
    ],

    /**
     * Tipos de documento para nómina / plantilla masivos (CLASEDOC.C1 = code).
     *
     * @var list<array{code: string, name: string, sort_order: int}>
     */
    'document_type_defaults' => [
        ['code' => 'C', 'name' => 'Cedula de ciudadania', 'sort_order' => 1],
        ['code' => 'CE', 'name' => 'Cedula de extranjeria', 'sort_order' => 2],
        ['code' => 'N', 'name' => 'NIT', 'sort_order' => 3],
        ['code' => 'TI', 'name' => 'Tarjeta de identidad', 'sort_order' => 4],
        ['code' => 'PT', 'name' => 'Permiso temporal', 'sort_order' => 5],
    ],

    /**
     * Valores base para catalogos de formulario / plantilla masivos (upsert en migrate o artisan).
     *
     * @var array<string, list<array{code: string, name: string, sort_order: int}>>
     */
    'catalog_static_defaults' => [
        'account_type' => [
            ['code' => '1', 'name' => 'Ahorros', 'sort_order' => 1],
            ['code' => '2', 'name' => 'Corriente', 'sort_order' => 2],
        ],
        'workday' => [
            ['code' => '1', 'name' => 'Tiempo completo', 'sort_order' => 1],
            ['code' => '2', 'name' => 'Medio tiempo', 'sort_order' => 2],
        ],
        'risk_level' => [
            ['code' => '1', 'name' => 'Riesgo I', 'sort_order' => 1],
            ['code' => '2', 'name' => 'Riesgo II', 'sort_order' => 2],
            ['code' => '3', 'name' => 'Riesgo III', 'sort_order' => 3],
            ['code' => '4', 'name' => 'Riesgo IV', 'sort_order' => 4],
            ['code' => '5', 'name' => 'Riesgo V', 'sort_order' => 5],
        ],
        'withholding_type' => [
            ['code' => '1', 'name' => 'Retencion en la fuente procedimiento 1', 'sort_order' => 1],
            ['code' => '2', 'name' => 'Retencion en la fuente procedimiento 2', 'sort_order' => 2],
        ],
        'expense_type' => [
            ['code' => '1', 'name' => 'Gasto personal', 'sort_order' => 1],
            ['code' => '2', 'name' => 'Gasto general', 'sort_order' => 2],
            ['code' => '3', 'name' => 'Gasto ventas', 'sort_order' => 3],
            ['code' => '4', 'name' => 'Gasto operacional', 'sort_order' => 4],
        ],
    ],

    /**
     * Columnas de plantilla masivos que no se capturan ni exportan (FEAT-028).
     *
     * @var list<string>
     */
    'plantilla_masivos_excluded_columns' => [
        'NITCENTROTB.C15',
    ],

    /**
     * Selector UI (catalog_type) por columna plantilla masivos con catálogo (FEAT-028).
     *
     * @var array<string, string>
     */
    'plantilla_masivos_catalog_columns' => [
        'CLASEDOC.C1' => 'document_type',
        'TIPOVNC.N1' => 'linkage_type',
        'FORPAGO.C10' => 'payment_method',
        'CODBANCO.C10' => 'bank',
        'TIPOCUENTA.N1' => 'account_type',
        'CODCENTROTB.C10' => 'work_center',
        'NOMCENTROTB.C30' => 'work_center',
        'TASAARP.C10' => 'risk_level',
        'CODTPSALAR.C10' => 'salary_type',
        'CODTPCONTR.C10' => 'contract_type',
        'JORNADA.N1' => 'workday',
        'CODCCOSTO.C10' => 'cost_center',
        'CODEPS.C10' => 'eps',
        'CODAFP.C10' => 'afp',
        'CODCCF.C10' => 'ccf',
        'TPRTFTE.N1' => 'withholding_type',
        'TPGASTO.N1' => 'expense_type',
    ],

    /**
     * Pares codigo → nombre en columnas de employee_ficha_profiles.
     *
     * @var list<array{code: string, name: string, type: string}>
     */
    'catalog_profile_code_name_pairs' => [
        ['code' => 'eps_code', 'name' => 'eps_name', 'type' => 'eps'],
        ['code' => 'afp_code', 'name' => 'afp_name', 'type' => 'afp'],
        ['code' => 'position_code', 'name' => 'position_name', 'type' => 'position'],
        ['code' => 'cost_center_code', 'name' => 'cost_center_name', 'type' => 'cost_center'],
        ['code' => 'bank_code', 'name' => 'bank_name', 'type' => 'bank'],
        ['code' => 'salary_type_code', 'name' => 'salary_type_name', 'type' => 'salary_type'],
        ['code' => 'contract_type_code', 'name' => 'contract_type_name', 'type' => 'contract_type'],
        ['code' => 'economic_activity_code', 'name' => 'economic_activity_name', 'type' => 'economic_activity'],
        ['code' => 'residence_city_code', 'name' => 'residence_city_name', 'type' => 'city'],
    ],

    /**
     * Pares codigo (payroll_extra) → nombre. target: profile | payroll_extra
     *
     * @var list<array{code: string, name: string, type: string, target: string}>
     */
    'catalog_payroll_extra_code_name_pairs' => [
        ['code' => 'work_center_code', 'name' => 'work_center_name', 'type' => 'work_center', 'target' => 'profile'],
        ['code' => 'ccf_code', 'name' => 'compensation_fund_name', 'type' => 'ccf', 'target' => 'profile'],
        ['code' => 'branch_code', 'name' => 'branch_name', 'type' => 'branch', 'target' => 'payroll_extra'],
        ['code' => 'destination_code', 'name' => 'destination_name', 'type' => 'destination', 'target' => 'payroll_extra'],
        ['code' => 'zone_code', 'name' => 'zone_name', 'type' => 'zone', 'target' => 'payroll_extra'],
        ['code' => 'severance_admin_code', 'name' => 'severance_admin_name', 'type' => 'severance_admin', 'target' => 'payroll_extra'],
    ],

    'import_columns' => [
        'cedula' => 'Cédula (obligatorio)',
        'nombre' => 'Nombre completo',
        'fecha_nac' => 'Fecha nacimiento (YYYY-MM-DD)',
        'tipo_documento' => 'Tipo documento (C, CE, N, TI, PT)',
        'codigo_lugar_exp_cedula' => 'Código lugar expedición',
        'lugar_exp_cedula' => 'Lugar expedición',
        'fecha_expedicion' => 'Fecha expedición',
        'codigo_lugar_residencia' => 'Código ciudad residencia DANE',
        'lugar_residencia' => 'Ciudad residencia',
        'direccion' => 'Dirección',
        'telefono' => 'Teléfono',
        'tipo_sangre' => 'Tipo sangre',
        'sexo' => 'Sexo (M/F)',
        'salario' => 'Salario',
        'escolaridad' => 'Escolaridad',
        'estado_civil' => 'Estado civil',
        'numero_hijos' => 'Número hijos',
        'email' => 'Correo electrónico',
        'tipo_vinculacion' => 'Tipo vinculación',
        'tipo_cotizante' => 'Tipo cotizante',
        'fecha_ingreso' => 'Fecha ingreso (YYYY-MM-DD)',
        'fecha_vencimiento_contrato' => 'Fecha vencimiento contrato',
        'fecha_retiro' => 'Fecha retiro (vacío = activo)',
        'nombre_centro_trabajo' => 'Nombre centro trabajo',
        'ccosto' => 'Código centro costo',
        'nombre_ccosto' => 'Nombre centro costo',
        'cargo' => 'Código cargo',
        'nombre_cargo' => 'Nombre cargo',
        'escala' => 'Escala',
        'tipo_salario' => 'Tipo salario',
        'tipo_contrato' => 'Tipo contrato',
        'codigo_eps' => 'Código EPS',
        'nombre_eps' => 'Nombre EPS',
        'codigo_afp' => 'Código AFP',
        'nombre_afp' => 'Nombre AFP',
        'nombre_arp' => 'Nombre ARP',
        'nivel_riesgo_arp' => 'Nivel riesgo ARP',
        'nombre_caja_compensacion' => 'Caja compensación',
        'banco' => 'Banco / código banco',
        'tipo_de_cuenta' => 'Tipo cuenta (1/2)',
        'cuenta' => 'Número cuenta',
        'forma_pago' => 'Forma pago',
        'actividad_economica' => 'Código actividad económica',
        'nombre_actividad_economica' => 'Nombre actividad económica',
        'codigo_requisicion' => 'Código requisición (opcional)',
    ],

    /*
    | Columnas adicionales solo para exportación Archivo (no forman parte del import masivo).
    */
    'archive_export_extra_columns' => [
        'estantes' => 'Estantes',
        'cajas' => 'Cajas',
    ],

    'archive_consultation_types' => [
        'ordenacion_clasificacion' => 'Ordenacion y clasificacion documental',
        'juridico' => 'Juridico',
        'gerencia' => 'Gerencia',
        'gestion_humana' => 'Gestion Humana',
        'reorganizacion_hl' => 'Reorganizacion de historias laborales',
        'suministro_ss' => 'Suministro de documentos de seguridad social',
        'cedulas_nomina_hl' => 'Cedulas a nomina y documentos HL para licitaciones',
        'auditorias' => 'Auditorias',
    ],

    'plantilla_masivos_template' => storage_path('templates/plantilla-masivos.xlsx'),

    'plantilla_masivos_columns' => [
        'TMPCEDULA.C15',
        'CLASEDOC.C1',
        'TMPNOMBRE.C40',
        'TMPAPELL_1.C60',
        'TMPAPELL_2.C60',
        'TMPNOMB_1.C60',
        'TMPNOMB_2.C60',
        'TMPDIRECCI.C40',
        'TMPTELEFON.C19',
        'TMPTELEF2.C19',
        'TMPEMAIL.C40',
        'TMPCIUDAD.C5',
        'TMPCIUNOM.C30',
        'FECNACIDO.C10',
        'FECHAING.C10',
        'FECHAVACA.C10',
        'TIPOVNC.N1',
        'CODCARGO.C10',
        'NOMCARGO.C30',
        'FORPAGO.C10',
        'CODBANCO.C10',
        'NOMBANCO.C30',
        'BANCUENTA.C20',
        'TIPOCUENTA.N1',
        'CODCENTROTB.C10',
        'NITCENTROTB.C15',
        'NOMCENTROTB.C30',
        'SALARIO.N12',
        'CODEPS.C10',
        'NOMEPS.C30',
        'FECINGEPS.C10',
        'CODAFP.C10',
        'NOMAFP.C30',
        'FECINGAFP.C10',
        'CODARP.C10',
        'NOMARP.C30',
        'TASAARP.C10',
        'CODCCF.C10',
        'NOMCCF.C30',
        'LIBRETA.C20',
        'SEXO.C1',
        'CODTPSALAR.C10',
        'NOMTPSALAR.C50',
        'CODTPCONTR.C10',
        'NOMTPCONTR.C50',
        'FECHAVCTO.C10',
        'JORNADA.N1',
        'TPRTFTE.N1',
        'TPGASTO.N1',
        'CODADCESAN.C10',
        'NOMADCESAN.C50',
        'CODSUCURS.C5',
        'NOMSUCURS.C50',
        'CODCCOSTO.C10',
        'NOMCCOSTO.C50',
        'CODDESTINO.C10',
        'NOMDESTINO.C50',
        'CODZONA.C5',
        'NOMZONA.C50',
        'CODACTARL.C10',
        'NOMACTARL.C50',
        'EXCLAUXTRA.N1',
    ],

    'catalog_types' => [
        'document_type',
        'city',
        'position',
        'cost_center',
        'work_center',
        'eps',
        'afp',
        'arp',
        'bank',
        'payment_method',
        'contract_type',
        'salary_type',
        'economic_activity',
        'branch',
        'destination',
        'zone',
        'severance_admin',
        'linkage_type',
        'account_type',
        'risk_level',
        'workday',
        'ccf',
        'withholding_type',
        'expense_type',
        'termination_cause',
    ],

    'catalog_type_labels' => [
        'document_type' => 'Tipo documento',
        'city' => 'Ciudad',
        'position' => 'Cargo',
        'cost_center' => 'Centro de costo',
        'work_center' => 'Centro de trabajo',
        'eps' => 'EPS',
        'afp' => 'AFP',
        'arp' => 'ARP',
        'bank' => 'Banco',
        'payment_method' => 'Forma de pago',
        'contract_type' => 'Tipo contrato',
        'salary_type' => 'Tipo salario',
        'economic_activity' => 'Actividad economica',
        'branch' => 'Sucursal',
        'destination' => 'Destino',
        'zone' => 'Zona',
        'severance_admin' => 'Administradora de cesantias',
        'linkage_type' => 'Tipo vinculacion',
        'account_type' => 'Tipo de cuenta',
        'risk_level' => 'Nivel riesgo ARP',
        'workday' => 'Jornada',
        'ccf' => 'Caja de compensacion',
        'withholding_type' => 'Tipo retencion en la fuente',
        'expense_type' => 'Tipo gasto',
        'termination_cause' => 'Causal desvinculacion',
        'firmas' => 'Firmas',
    ],

    /**
     * Etiquetas de columnas personalizadas por tipo de catalogo.
     * Si un tipo no esta aqui, se usan los defaults "Codigo" y "Nombre".
     *
     * @var array<string, array{code: string, name: string}>
     */
    'catalog_column_labels' => [
        'firmas' => ['code' => 'Cargo', 'name' => 'Nombre'],
    ],

    'termination_cause_defaults' => [
        ['code' => 'RENUNCIA', 'name' => 'Renuncia voluntaria', 'sort_order' => 1],
        ['code' => 'FIN_CONTRATO', 'name' => 'Fin de contrato', 'sort_order' => 2],
        ['code' => 'DESPIDO', 'name' => 'Despido', 'sort_order' => 3],
        ['code' => 'MUTUO_ACUERDO', 'name' => 'Terminacion por mutuo acuerdo', 'sort_order' => 4],
        ['code' => 'PERIODO_PRUEBA', 'name' => 'No supera periodo de prueba', 'sort_order' => 5],
    ],

    /**
     * Codes estables de tipos de documento Word (seed BD; no hardcodear en servicios).
     *
     * @var array<string, string>
     */
    'word_document_type_codes' => [
        'desvinculacion' => 'desvinculacion',
    ],

    'termination_letter_signatory' => [
        'name' => env('FICHA_LETTER_SIGNATORY_NAME', 'Directora de Gestion Humana'),
        'title' => env('FICHA_LETTER_SIGNATORY_TITLE', 'Directora de Gestion Humana'),
    ],

    /**
     * Placeholders soportados en plantillas Word ([NOMBRE], [CEDULA], ...).
     *
     * @var array<string, string>
     */
    'termination_letter_placeholders' => [
        'FECHA' => 'Fecha de la carta',
        'NOMBRE' => 'Nombre completo del empleado',
        'CEDULA' => 'Documento de identidad',
        'CARGO' => 'Cargo',
        'CIUDAD' => 'Ciudad (centro de trabajo o residencia)',
        'FECHA_TERMINACION' => 'Ultimo dia de labores',
        'FECHA_INGRESO' => 'Fecha de ingreso',
        'SALARIO' => 'Salario base',
        'TIPO_CONTRATO' => 'Tipo de contrato',
        'FIRMA' => 'Nombre del firmante GH',
        'CARGO_FIRMA' => 'Cargo del firmante GH',
    ],
];

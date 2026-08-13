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
        ['code' => 'C', 'name' => 'Cedula', 'sort_order' => 1],
        ['code' => 'N', 'name' => 'Nit', 'sort_order' => 2],
        ['code' => 'TI', 'name' => 'Tarjeta de Identidad', 'sort_order' => 3],
        ['code' => 'PT', 'name' => 'Permiso temporal', 'sort_order' => 4],
    ],

    'import_columns' => [
        'cedula' => 'Cédula (obligatorio)',
        'nombre' => 'Nombre completo',
        'fecha_nac' => 'Fecha nacimiento (YYYY-MM-DD)',
        'tipo_documento' => 'Tipo documento (C, TI, etc.)',
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
        'eps',
        'afp',
        'arp',
        'bank',
        'payment_method',
        'contract_type',
        'salary_type',
        'economic_activity',
        'branch',
        'termination_cause',
    ],

    'catalog_type_labels' => [
        'document_type' => 'Tipo documento',
        'city' => 'Ciudad',
        'position' => 'Cargo',
        'cost_center' => 'Centro de costo',
        'eps' => 'EPS',
        'afp' => 'AFP',
        'arp' => 'ARP',
        'bank' => 'Banco',
        'payment_method' => 'Forma de pago',
        'contract_type' => 'Tipo contrato',
        'salary_type' => 'Tipo salario',
        'economic_activity' => 'Actividad economica',
        'branch' => 'Sucursal',
        'termination_cause' => 'Causal desvinculacion',
    ],

    'termination_cause_defaults' => [
        ['code' => 'RENUNCIA', 'name' => 'Renuncia voluntaria', 'sort_order' => 1],
        ['code' => 'FIN_CONTRATO', 'name' => 'Fin de contrato', 'sort_order' => 2],
        ['code' => 'DESPIDO', 'name' => 'Despido', 'sort_order' => 3],
        ['code' => 'MUTUO_ACUERDO', 'name' => 'Terminacion por mutuo acuerdo', 'sort_order' => 4],
        ['code' => 'PERIODO_PRUEBA', 'name' => 'No supera periodo de prueba', 'sort_order' => 5],
    ],

    /**
     * Causales con generacion de cartas habilitada en v1.
     *
     * @var list<string>
     */
    'termination_letter_supported_causes' => [
        'RENUNCIA',
    ],

    'termination_letter_signatory' => [
        'name' => env('FICHA_LETTER_SIGNATORY_NAME', 'Directora de Gestion Humana'),
        'title' => env('FICHA_LETTER_SIGNATORY_TITLE', 'Directora de Gestion Humana'),
    ],

    /**
     * Paquetes de documentos por causal (document_key + etiqueta).
     *
     * @var array<string, list<array{key: string, label: string, sort: int}>>
     */
    'termination_letter_packs' => [
        'RENUNCIA' => [
            ['key' => 'aceptacion_renuncia', 'label' => 'Aceptacion Carta de Renuncia', 'sort' => 1],
            ['key' => 'autorizacion_examen_retiro', 'label' => 'Autorizacion examen de retiro', 'sort' => 2],
            ['key' => 'certificado_laboral', 'label' => 'Certificado Laboral', 'sort' => 3],
        ],
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

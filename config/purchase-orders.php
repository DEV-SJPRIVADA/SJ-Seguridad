<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FO-AD-07 — Orden de Compra
    |--------------------------------------------------------------------------
    | Metadatos oficiales del formato (Enero de 2025 Versión 04). El número de OC
    | visible es el `id` autoincremental de `purchase_orders` (sin prefijo/pad).
    */
    'form_code' => env('PURCHASE_ORDER_FORM_CODE', 'FO-AD-07'),
    'form_version' => env('PURCHASE_ORDER_FORM_VERSION', '04'),
    'form_period' => env('PURCHASE_ORDER_FORM_PERIOD', 'Enero de 2025'),
    'report_title' => env('PURCHASE_ORDER_REPORT_TITLE', 'ORDEN DE COMPRA O SERVICIO'),
    'company' => env('PURCHASE_ORDER_COMPANY', 'SJ Seguridad'),

    /*
    | Texto legal del FO-AD-07. Copiado tal cual del formato oficial:
    | no normalizar mayúsculas, tildes ni espacios dobles.
    */
    'condiciones' => rtrim(<<<'TXT'
CONDICIONES GENERALES: AL ACEPTAR ESTA ORDEN DE COMPRA EL ASOCIADO DE NEGOCIO SE COMPROMETE A LAS SIGUIENTES CONDICIONES:

LA FACTURA O CUENTA DE COBRO QUE GENERE ESTA ORDEN  SERA CANCELADA PREVIA PRESENTACIÓN DE LA MISMA, A PARTIR DE SU FECHA DE RADICACION, EN EL PLAZO ACORDADO POR LAS PARTES, SIN QUE SE PRODUZCA INTERES ALGUNO.

EL ASOCIADO DE NEGOCIO DEBE RADICAR FACTURA O CUENTA DE COBRO EN ORIGINAL Y COPIA, RELACIONANDO EL NUMERO DE LA ORDEN  Y ANEXAR COPIA DE LA REMISION O ACTA DE ENTREGA CON SELLO DE RECIBIDO DEL ALMACEN;  ESTA FACTURA O CUENTA DE COBRO SOLO SERÁ ACEPTADA CUANDO EL SERVICIO CORRESPONDA A LA ORDEN EMITIDA.

PARA EFECTOS CONTABLES  SOLO SE RADICARAN FACTURAS HASTA EL DIA VEINTICINCO(25) DEL MES EN CURSO, POR LO TANTO LAS FACTURAS O CUENTAS DE COBRO QUE SE GENEREN A PARTIR DE ESA FECHA DEBERAN RADICARSE CON FECHA DEL MES SIGUIENTE.

EL ASOCIADO DE NEGOCIO SE COMPROMETE A REALIZAR LA REPARACION O PRESTACION DEL SERVICIO EN LOS TERMINOS QUE ESTIPULA ESTA ORDEN, HACIENDOSE RESPONSABLE POR LOS DAÑOS O PERDIDAS QUE SE CAUSAREN AL BIEN O BIENES PROPIEDAD DE ESTA EMPRESA EN EL DESARROLLO DE LA LABOR SOLICITADA.

LA PRESENTE ORDEN NO CONSTITUYE DEPENDENCIA LABORAL ALGUNA.

ESTA ORDEN DE COMPRA ES CONCORDANTE CON LO ESTIPULADO EN LA COTIZACION POR PARTE DEL ASOCIADO DE NEGOCIO.

VER AL RESPALDO LAS CONDICIONES LEGALES DE LA RELACIÓN COMERCIAL
TXT),

    'estados' => [
        'borrador' => 'Borrador',
        'emitida' => 'Emitida',
    ],

    /*
    | Evaluación post-entrega (no se imprime en FO-AD-07).
    | Pesos fijos 25/25/50; calificación entera 1–5.
    */
    'evaluation' => [
        'weights' => [
            'referencias_cantidades' => 25,
            'oportunidad_entrega' => 25,
            'calidad_producto' => 50,
        ],
        'thresholds' => [
            'satisfactoria' => 80,
            'seguimiento' => 60,
        ],
        'labels' => [
            'satisfactoria' => 'Compra satisfactoria',
            'seguimiento' => 'Requiere seguimiento',
            'insatisfactoria' => 'Compra insatisfactoria',
        ],
    ],
];

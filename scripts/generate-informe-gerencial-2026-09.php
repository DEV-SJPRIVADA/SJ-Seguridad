<?php

declare(strict_types=1);

/**
 * Genera informe gerencial septiembre 2026 (Word).
 * Uso: php scripts/generate-informe-gerencial-2026-09.php
 */

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

$outDir = __DIR__.'/../docs/informes';
if (! is_dir($outDir) && ! mkdir($outDir, 0755, true) && ! is_dir($outDir)) {
    throw new RuntimeException('No se pudo crear docs/informes');
}

$phpWord = new PhpWord;
$phpWord->setDefaultFontName('Calibri');
$phpWord->setDefaultFontSize(11);

$phpWord->addTitleStyle(1, ['bold' => true, 'size' => 16, 'color' => '1F4E79']);
$phpWord->addTitleStyle(2, ['bold' => true, 'size' => 13, 'color' => '2E75B6']);
$phpWord->addTitleStyle(3, ['bold' => true, 'size' => 12, 'color' => '404040']);

$tableStyle = [
    'borderSize' => 6,
    'borderColor' => 'BFBFBF',
    'cellMargin' => 80,
];
$tableHeader = ['bgColor' => '2E75B6', 'bold' => true, 'color' => 'FFFFFF'];
$cellWidth = ['val' => 2500];

$section = $phpWord->addSection(['marginTop' => 1200, 'marginBottom' => 1200]);

$section->addTitle('Informe gerencial — SJ StatFlow', 1);
$section->addText('SJ Seguridad · Plataforma web modular', ['italic' => true, 'color' => '666666']);
$section->addTextBreak(1);
$section->addText('Periodo: septiembre 2026', ['bold' => true]);
$section->addText('Elaborado para: dirección y gerencia');
$section->addText('Fuente: entregas registradas en repositorio, tablero FEAT-031–039 y commits del periodo.');
$section->addTextBreak(2);

$section->addTitle('1. Resumen ejecutivo', 2);
$section->addText(
    'Septiembre concentró el avance en Gestión Humana (GH): desvinculaciones, cursos, selección y acreditaciones '
    .'(incluido cruce diario con reportes APO). En paralelo se formalizó el canal TIC de solicitudes de desarrollo '
    .'y se reforzaron requisiciones, ficha de empleados y experiencia de usuario en la plataforma.'
);
$section->addTextBreak(1);
$section->addText('Indicadores del mes:', ['bold' => true]);
$section->addListItem('37 commits en el periodo (14–28 sep).');
$section->addListItem('5 features cerradas y validadas (FEAT-031, 035, 036, 037, 038).');
$section->addListItem('4 iniciativas en cierre o desarrollo activo al cierre del mes.');
$section->addTextBreak(1);
$section->addText(
    'Mensaje clave: GH gana trazabilidad, control de vigencias y validaciones cruzadas; el ecosistema de Acreditaciones '
    .'queda operativo salvo Export APO SuperVigilancia y Dashboard (en curso).',
    ['italic' => true]
);

$section->addTitle('2. Valor de negocio', 2);
$addValueTable = function () use ($section, $tableStyle, $tableHeader, $cellWidth): void {
    $table = $section->addTable($tableStyle);
    $row = $table->addRow();
    $row->addCell(1800, $tableHeader)->addText('Prioridad');
    $row->addCell(3500, $tableHeader)->addText('Entrega');
    $row->addCell(4500, $tableHeader)->addText('Beneficio');
    $rows = [
        ['Alta', 'Acreditaciones (acreditados, reporte diario, validaciones)', 'Control APO y discrepancias ficha vs sistema vs reporte.'],
        ['Alta', 'Selección (ingreso + examen + dashboard)', 'KPIs, filtros, export; referido obligatorio en ingresos.'],
        ['Alta', 'Desvinculaciones (masivos + seguimientos)', 'Lotes con ZIP de cartas y checklist post-retiro.'],
        ['Media-alta', 'Cursos', 'Vigencia automática, catálogo, import/export, documentos por curso.'],
        ['Media', 'Solicitudes desarrollo TIC', 'FO-TIC-23 digital, aprobación líder, bandeja TIC, chat.'],
        ['Media', 'Ficha empleados', 'Campos ampliados, permisos de copia/edición, import mejorado.'],
        ['Operativo', 'Requisiciones', 'Impresión, destinatarios adicionales en notificaciones.'],
    ];
    foreach ($rows as [$p, $e, $b]) {
        $row = $table->addRow();
        $row->addCell(1800, $cellWidth)->addText($p);
        $row->addCell(3500, $cellWidth)->addText($e);
        $row->addCell(4500, $cellWidth)->addText($b);
    }
};
$addValueTable();
$section->addTextBreak(1);

$section->addTitle('3. Entregas por área', 2);

$section->addTitle('3.1 Gestión Humana', 3);
$gh = [
    'Desvinculaciones (14 sep): masivos por grilla, ZIP de cartas, seguimientos con OK TODO automático.',
    'Cursos (15–17 sep): tablero con vigencia, catálogo, plantilla/carga masiva, export Excel, documentos adjuntos.',
    'Selección (22 sep): dashboard, ingresos, examen ocupacional, catálogos; DataTables server-side.',
    'Acreditaciones (22–28 sep): acreditados con estados automáticos; reporte diario (2 Excel APO); '
    .'validaciones con cuatro colas accionables; ajustes de renovaciones y masivo en proceso.',
];
foreach ($gh as $item) {
    $section->addListItem($item);
}

$section->addTitle('3.2 TIC, compras y transversal', 3);
$other = [
    'TIC: módulo Solicitudes de desarrollo (estados, aprobación director/líder, chat con correo).',
    'Compras: comentarios post-envío; plantilla masiva para precarga en nueva solicitud.',
    'Requisiciones: botón imprimir; ajustes de formato solicitados por dirección GH.',
    'UX/marca: iconografía unificada, tipografía corporativa, filtros reclutador en dashboard GH.',
];
foreach ($other as $item) {
    $section->addListItem($item);
}

$section->addTitle('4. Estado de features (tablero proyecto)', 2);
$statusTable = $section->addTable($tableStyle);
$row = $statusTable->addRow();
$row->addCell(1200, $tableHeader)->addText('Estado');
$row->addCell(1200, $tableHeader)->addText('ID');
$row->addCell(5000, $tableHeader)->addText('Entrega');
$row->addCell(1800, $tableHeader)->addText('Fecha ref.');
$featRows = [
    ['Cerrada', 'FEAT-031', 'Desvinculaciones (Masivos + Seguimientos)', '14-sep'],
    ['Cerrada', 'FEAT-035', 'Tablero Selección GH', '22-sep'],
    ['Cerrada', 'FEAT-036', 'Acreditaciones — base operativa', '23-sep'],
    ['Cerrada', 'FEAT-037', 'Reporte Diario APO', '24-sep'],
    ['Cerrada', 'FEAT-038', 'Validaciones Acreditaciones', '25-sep'],
    ['En cierre', 'FEAT-032', 'Tablero Cursos', '15–17 sep'],
    ['En cierre', 'FEAT-033', 'Solicitudes desarrollo TIC', '16–17 sep'],
    ['En progreso', 'FEAT-034', 'Cola «sin curso» + hooks ficha', '21-sep'],
    ['En progreso', 'FEAT-039', 'Export APO + Dashboard Acreditaciones', '28-sep'],
];
foreach ($featRows as [$st, $id, $name, $dt]) {
    $row = $statusTable->addRow();
    $row->addCell(1200, $cellWidth)->addText($st);
    $row->addCell(1200, $cellWidth)->addText($id);
    $row->addCell(5000, $cellWidth)->addText($name);
    $row->addCell(1800, $cellWidth)->addText($dt);
}

$section->addTextBreak(1);
$section->addTitle('5. Calidad, seguridad y gobernanza', 2);
$section->addListItem('Permisos por tablero (view/edit) y auditoría central donde aplica.');
$section->addListItem('Pruebas automatizadas en módulos críticos (acreditaciones, validaciones, import ficha).');
$section->addListItem('Migraciones aditivas; sin reset de base de datos en operación.');
$section->addListItem('Documentación viva (briefs, run logs, docs de módulo) alineada al código.');

$section->addTitle('6. Riesgos y mitigación', 2);
$riskTable = $section->addTable($tableStyle);
$row = $riskTable->addRow();
$row->addCell(2500, $tableHeader)->addText('Tema');
$row->addCell(3500, $tableHeader)->addText('Observación');
$row->addCell(4500, $tableHeader)->addText('Mitigación');
foreach ([
    ['Adopción GH', 'Varios tableros nuevos en un mes', 'Capacitación por tablero (orden sugerido: Desvinculaciones → Cursos → Selección → Acreditaciones).'],
    ['Permisos', 'Tableros no asignados por defecto al rol genérico', 'Matriz rol–permiso con GH y TI antes de go-live masivo.'],
    ['Acreditaciones', 'Export APO en desarrollo', 'Priorizar FEAT-039 para cerrar ciclo SuperVigilancia.'],
    ['Datos maestros', 'Validaciones exigen ficha y cargas APO del día', 'Rutina: Reporte Diario → Validaciones.'],
] as [$t, $o, $m]) {
    $row = $riskTable->addRow();
    $row->addCell(2500, $cellWidth)->addText($t);
    $row->addCell(3500, $cellWidth)->addText($o);
    $row->addCell(4500, $cellWidth)->addText($m);
}

$section->addTitle('7. Próximos pasos sugeridos (octubre 2026)', 2);
$steps = [
    'Cerrar formalmente Cursos (FEAT-032), TIC (FEAT-033) y cola sin curso (FEAT-034).',
    'Completar Export APO + Dashboard (FEAT-039) y validar con lote real SuperVigilancia.',
    'Capacitación GH: flujo diario Acreditaciones (carga → validación → acciones en colas).',
    'Revisión de permisos por perfil (reclutador, acreditaciones, cursos).',
];
foreach ($steps as $i => $step) {
    $section->addListItem($step, 0, ['bold' => false]);
}

$section->addTextBreak(1);
$section->addTitle('8. Conclusión', 2);
$section->addText(
    'Septiembre consolidó SJ StatFlow como herramienta operativa para el ciclo de vida del personal en GH: '
    .'selección, cursos, acreditaciones, desvinculación y seguimiento. El hito más relevante es el módulo de '
    .'Acreditaciones con validaciones cruzadas, que reduce errores entre ficha interna, registro propio y reportes APO.'
);

$section->addTextBreak(2);
$section->addText('— Fin del informe —', ['alignment' => Jc::CENTER, 'color' => '888888', 'size' => 10]);

$path = $outDir.'/informe-gerencial-statflow-2026-09.docx';
IOFactory::createWriter($phpWord, 'Word2007')->save($path);

echo "Generado: {$path}\n";

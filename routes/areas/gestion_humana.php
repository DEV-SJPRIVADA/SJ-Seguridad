<?php

use App\Http\Controllers\GestionHumana\AcreditacionesController;
use App\Http\Controllers\GestionHumana\ArchivoController;
use App\Http\Controllers\GestionHumana\ContratacionLetterController;
use App\Http\Controllers\GestionHumana\CursosCatalogController;
use App\Http\Controllers\GestionHumana\CursosController;
use App\Http\Controllers\GestionHumana\DesvinculacionesController;
use App\Http\Controllers\GestionHumana\FichaEmpleadosCatalogController;
use App\Http\Controllers\GestionHumana\FichaEmpleadosController;
use App\Http\Controllers\GestionHumana\PlantillasWordController;
use App\Http\Controllers\GestionHumana\SeleccionController;
use App\Http\Controllers\GestionHumana\TerminationLetterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/ficha-empleados/catalogos')
    ->name('gestion-humana.ficha-empleados.catalogs.')
    ->group(function (): void {
        Route::get('/', [FichaEmpleadosCatalogController::class, 'index'])->name('index');
        Route::post('/{type}', [FichaEmpleadosCatalogController::class, 'store'])->name('store');
        Route::patch('/{type}/{item}', [FichaEmpleadosCatalogController::class, 'update'])->name('update');
        Route::delete('/{type}/{item}', [FichaEmpleadosCatalogController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/ficha-empleados/empleados')
    ->name('gestion-humana.ficha-empleados.employees.')
    ->group(function (): void {
        Route::get('/nuevo', [FichaEmpleadosController::class, 'create'])->name('create');
        Route::post('/nuevo', [FichaEmpleadosController::class, 'store'])->name('store');
        Route::get('/', [FichaEmpleadosController::class, 'index'])->name('index');
        Route::get('/datatable', [FichaEmpleadosController::class, 'datatable'])->name('datatable');
        Route::get('/exportar', [FichaEmpleadosController::class, 'exportExcel'])->name('export');
        Route::get('/plantilla-importacion', [FichaEmpleadosController::class, 'importTemplate'])->name('import-template');
        Route::get('/plantilla-importacion/exportar', [FichaEmpleadosController::class, 'exportImportTemplate'])->name('export-import-template');
        Route::get('/exportar-archivo', [FichaEmpleadosController::class, 'exportArchiveTemplate'])->name('export-archive-template');
        Route::post('/importar', [FichaEmpleadosController::class, 'import'])->name('import');
        Route::get('/importar/reporte/{token}', [FichaEmpleadosController::class, 'downloadImportReport'])->name('import-report');
        Route::get('/{fichaEntry}/ficha', [FichaEmpleadosController::class, 'editFicha'])->name('ficha.edit');
        Route::patch('/{fichaEntry}/ficha', [FichaEmpleadosController::class, 'updateFicha'])->name('ficha.update');
        Route::post('/{fichaEntry}/desvincular', [FichaEmpleadosController::class, 'terminate'])->name('ficha.terminate');
        Route::get('/{fichaEntry}/cursos', [FichaEmpleadosController::class, 'employeeCursos'])->name('cursos');
        Route::get('/{fichaEntry}/cursos/{employeeCurso}/documento', [FichaEmpleadosController::class, 'downloadEmployeeCursoDocument'])->name('cursos.document');
        Route::get('/periodos/{period}/cartas/plantillas', [TerminationLetterController::class, 'templates'])->name('period.letters.templates');
        Route::post('/periodos/{period}/cartas/generar', [TerminationLetterController::class, 'generate'])->name('period.letters.generate');
        Route::get('/periodos/{period}/cartas/descargar', [TerminationLetterController::class, 'download'])->name('period.letters.download');
        Route::get('/periodos/{period}/cartas/firmas', [TerminationLetterController::class, 'firmas'])->name('period.letters.firmas');
        Route::get('/periodos/{period}/contratacion/plantillas', [ContratacionLetterController::class, 'templates'])->name('contratacion.templates');
        Route::post('/periodos/{period}/contratacion/generar', [ContratacionLetterController::class, 'generate'])->name('contratacion.generate');
        Route::get('/periodos/{period}/contratacion/firmas', [ContratacionLetterController::class, 'firmas'])->name('contratacion.firmas');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/archivo')
    ->name('gestion-humana.archivo.')
    ->group(function (): void {
        Route::get('/', [ArchivoController::class, 'index'])->name('index');
        Route::get('/historias-laborales', [ArchivoController::class, 'laborHistories'])->name('labor-histories.index');
        Route::get('/historias-laborales/datatable', [ArchivoController::class, 'laborHistoriesDatatable'])->name('labor-histories.datatable');
        Route::get('/historial-consultas', [ArchivoController::class, 'consultationHistory'])->name('consultation-history.index');
        Route::post('/consultar', [ArchivoController::class, 'consult'])->name('consult');
        Route::patch('/historial-consultas/{consultationItem}', [ArchivoController::class, 'updateConsultationItem'])->name('consultation-history.update');
        Route::post('/importar', [ArchivoController::class, 'import'])->name('import');
        Route::get('/importar/reporte/{token}', [ArchivoController::class, 'downloadImportReport'])->name('import-report');
        Route::patch('/{fichaEntry}', [ArchivoController::class, 'update'])->name('update');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/plantillas-word')
    ->name('gestion-humana.plantillas-word.')
    ->group(function (): void {
        Route::get('/', [PlantillasWordController::class, 'index'])->name('index');

        Route::post('/tipos', [PlantillasWordController::class, 'storeType'])->name('types.store');
        Route::patch('/tipos/{type}', [PlantillasWordController::class, 'updateType'])->name('types.update');
        Route::delete('/tipos/{type}', [PlantillasWordController::class, 'destroyType'])->name('types.destroy');

        Route::post('/plantillas', [PlantillasWordController::class, 'storeTemplate'])->name('templates.store');
        Route::post('/plantillas/{template}/reemplazar', [PlantillasWordController::class, 'replaceTemplate'])->name('templates.replace');
        Route::delete('/plantillas/{template}', [PlantillasWordController::class, 'destroyTemplate'])->name('templates.destroy');
        Route::get('/plantillas/{template}/descargar', [PlantillasWordController::class, 'downloadTemplate'])->name('templates.download');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/desvinculaciones')
    ->name('gestion-humana.desvinculaciones.')
    ->group(function (): void {
        Route::get('/', [DesvinculacionesController::class, 'index'])->name('index');
        Route::get('/masivos', [DesvinculacionesController::class, 'masivos'])->name('masivos');
        Route::get('/masivos/plantillas', [DesvinculacionesController::class, 'templates'])->name('masivos.templates');
        Route::get('/masivos/firmas', [DesvinculacionesController::class, 'signatories'])->name('masivos.signatories');
        Route::post('/masivos/lookup', [DesvinculacionesController::class, 'lookup'])->name('masivos.lookup');
        Route::post('/masivos/procesar', [DesvinculacionesController::class, 'process'])->name('masivos.process');
        Route::get('/masivos/descarga/{token}', [DesvinculacionesController::class, 'downloadZip'])->name('masivos.download');
        Route::get('/seguimientos', [DesvinculacionesController::class, 'seguimientos'])->name('seguimientos');
        Route::get('/seguimientos/datatable', [DesvinculacionesController::class, 'seguimientosDatatable'])->name('seguimientos.datatable');
        Route::get('/seguimientos/exportar', [DesvinculacionesController::class, 'exportSeguimientos'])->name('seguimientos.export');
        Route::patch('/seguimientos/{followup}', [DesvinculacionesController::class, 'updateSeguimiento'])->name('seguimientos.update');
        Route::post('/seguimientos/{followup}/revertir', [DesvinculacionesController::class, 'revertSeguimiento'])->name('seguimientos.revert');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/cursos')
    ->name('gestion-humana.cursos.')
    ->group(function (): void {
        Route::get('/', [CursosController::class, 'index'])->name('index');
        Route::get('/dashboard', [CursosController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/metrics', [CursosController::class, 'dashboardMetrics'])->name('dashboard.metrics');
        Route::get('/registros', [CursosController::class, 'registros'])->name('registros');
        Route::get('/registros/datatable', [CursosController::class, 'datatable'])->name('registros.datatable');
        Route::get('/registros/bulk-selectable', [CursosController::class, 'bulkSelectable'])->name('registros.bulk-selectable');
        Route::get('/registros/lookup', [CursosController::class, 'lookup'])->name('registros.lookup');
        Route::get('/registros/exportar', [CursosController::class, 'export'])->name('registros.export');
        Route::get('/registros/plantilla-importacion', [CursosController::class, 'importTemplate'])->name('registros.import-template');
        Route::post('/registros/importar', [CursosController::class, 'import'])->name('registros.import');
        Route::get('/registros/importar/reporte/{token}', [CursosController::class, 'downloadImportReport'])->name('registros.import-report');
        Route::post('/registros', [CursosController::class, 'store'])->name('registros.store');
        Route::post('/registros/marcar-solicitado', [CursosController::class, 'bulkMarkSolicitado'])->name('registros.bulk-mark-solicitado');
        Route::post('/registros/pendientes/{pending}/omitir', [CursosController::class, 'omitPending'])
            ->name('registros.pendientes.omit');
        Route::patch('/registros/{employeeCurso}', [CursosController::class, 'update'])->name('registros.update');
        Route::delete('/registros/{employeeCurso}', [CursosController::class, 'destroy'])->name('registros.destroy');
        Route::get('/registros/{employeeCurso}/documento', [CursosController::class, 'downloadDocument'])->name('registros.document.download');
        Route::post('/registros/{employeeCurso}/documento', [CursosController::class, 'uploadDocument'])->name('registros.document.upload');
        Route::delete('/registros/{employeeCurso}/documento', [CursosController::class, 'destroyDocument'])->name('registros.document.destroy');

        Route::get('/catalogo', [CursosCatalogController::class, 'index'])->name('catalogo');
        Route::get('/catalogo/opciones', [CursosCatalogController::class, 'options'])->name('catalogo.options');
        Route::post('/catalogo/escuelas', [CursosCatalogController::class, 'storeEscuela'])->name('catalogo.escuelas.store');
        Route::patch('/catalogo/escuelas/{cursoEscuela}', [CursosCatalogController::class, 'updateEscuela'])->name('catalogo.escuelas.update');
        Route::delete('/catalogo/escuelas/{cursoEscuela}', [CursosCatalogController::class, 'destroyEscuela'])->name('catalogo.escuelas.destroy');
        Route::post('/catalogo', [CursosCatalogController::class, 'store'])->name('catalogo.store');
        Route::patch('/catalogo/{cursoTipo}', [CursosCatalogController::class, 'update'])->name('catalogo.update');
        Route::delete('/catalogo/{cursoTipo}', [CursosCatalogController::class, 'destroy'])->name('catalogo.destroy');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/seleccion')
    ->name('gestion-humana.seleccion.')
    ->group(function (): void {
        Route::get('/', [SeleccionController::class, 'index'])->name('index');
        Route::get('/dashboard', [SeleccionController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/metrics', [SeleccionController::class, 'dashboardMetrics'])->name('dashboard.metrics');

        Route::get('/ingresos', [SeleccionController::class, 'ingresos'])->name('ingresos');
        Route::get('/ingresos/datatable', [SeleccionController::class, 'ingresosDatatable'])->name('ingresos.datatable');
        Route::get('/ingresos/lookup-cedula', [SeleccionController::class, 'ingresosLookupCedula'])->name('ingresos.lookup-cedula');
        Route::get('/ingresos/exportar', [SeleccionController::class, 'ingresosExport'])->name('ingresos.export');
        Route::post('/ingresos', [SeleccionController::class, 'storeIngreso'])->name('ingresos.store');
        Route::patch('/ingresos/{seleccionIngreso}', [SeleccionController::class, 'updateIngreso'])->name('ingresos.update');
        Route::delete('/ingresos/{seleccionIngreso}', [SeleccionController::class, 'destroyIngreso'])->name('ingresos.destroy');

        Route::get('/examenes', [SeleccionController::class, 'examenes'])->name('examenes');
        Route::get('/examenes/datatable', [SeleccionController::class, 'examenesDatatable'])->name('examenes.datatable');
        Route::get('/examenes/lookup-cedula', [SeleccionController::class, 'examenesLookupCedula'])->name('examenes.lookup-cedula');
        Route::get('/examenes/exportar', [SeleccionController::class, 'examenesExport'])->name('examenes.export');
        Route::post('/examenes', [SeleccionController::class, 'storeExamen'])->name('examenes.store');
        Route::patch('/examenes/{seleccionExamenOcupacional}', [SeleccionController::class, 'updateExamen'])->name('examenes.update');
        Route::delete('/examenes/{seleccionExamenOcupacional}', [SeleccionController::class, 'destroyExamen'])->name('examenes.destroy');

        Route::get('/catalogos', [SeleccionController::class, 'catalogos'])->name('catalogos');
        Route::post('/catalogos/{type}', [SeleccionController::class, 'storeCatalog'])->name('catalogos.store');
        Route::patch('/catalogos/{type}/{item}', [SeleccionController::class, 'updateCatalog'])->name('catalogos.update');
        Route::delete('/catalogos/{type}/{item}', [SeleccionController::class, 'destroyCatalog'])->name('catalogos.destroy');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/acreditaciones')
    ->name('gestion-humana.acreditaciones.')
    ->group(function (): void {
        Route::get('/', [AcreditacionesController::class, 'index'])->name('index');
        Route::get('/dashboard', [AcreditacionesController::class, 'dashboard'])->name('dashboard');
        Route::get('/acreditados', [AcreditacionesController::class, 'acreditados'])->name('acreditados');
        Route::get('/acreditados/datatable', [AcreditacionesController::class, 'acreditadosDatatable'])->name('acreditados.datatable');
        Route::get('/acreditados/lookup', [AcreditacionesController::class, 'acreditadosLookup'])->name('acreditados.lookup');
        Route::get('/acreditados/exportar', [AcreditacionesController::class, 'exportAcreditados'])->name('acreditados.export');
        Route::get('/acreditados/plantilla-importacion', [AcreditacionesController::class, 'importTemplate'])->name('acreditados.import-template');
        Route::post('/acreditados/importar', [AcreditacionesController::class, 'importAcreditados'])->name('acreditados.import');
        Route::get('/acreditados/importar/reporte/{token}', [AcreditacionesController::class, 'downloadImportReport'])->name('acreditados.import-report');
        Route::post('/acreditados', [AcreditacionesController::class, 'storeAcreditado'])->name('acreditados.store');
        Route::patch('/acreditados/{acreditacionAcreditado}', [AcreditacionesController::class, 'updateAcreditado'])->name('acreditados.update');
        Route::delete('/acreditados/{acreditacionAcreditado}', [AcreditacionesController::class, 'destroyAcreditado'])->name('acreditados.destroy');
        Route::get('/reporte-diario', [AcreditacionesController::class, 'reporteDiario'])->name('reporte-diario');
        Route::get('/validaciones', [AcreditacionesController::class, 'validaciones'])->name('validaciones');
        Route::get('/export-apo', [AcreditacionesController::class, 'exportApo'])->name('export-apo');
        Route::get('/catalogo', [AcreditacionesController::class, 'catalogo'])->name('catalogo');
        Route::post('/catalogo', [AcreditacionesController::class, 'storeCatalogo'])->name('catalogo.store');
        Route::patch('/catalogo/{acreditacionCargo}', [AcreditacionesController::class, 'updateCatalogo'])->name('catalogo.update');
        Route::delete('/catalogo/{acreditacionCargo}', [AcreditacionesController::class, 'destroyCatalogo'])->name('catalogo.destroy');
    });

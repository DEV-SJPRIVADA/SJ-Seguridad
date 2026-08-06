<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Concerns\HandlesImportFailureReports;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ImportEmployeeArchiveRequest;
use App\Http\Requests\GestionHumana\UpdateEmployeeArchiveRequest;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\Access\ArchivoAccessService;
use App\Services\GestionHumana\EmployeeArchiveImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchivoController extends Controller
{
    use HandlesImportFailureReports;

    public function __construct(
        private readonly ArchivoAccessService $archivoAccess,
        private readonly EmployeeArchiveImportService $importService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $importResult = session('import_result');
        $importHasFailures = is_array($importResult) && (($importResult['failures_count'] ?? 0) > 0 || ! empty($importResult['report_token']));

        $entries = PersonalRequisitionFichaEntry::query()
            ->inFicha()
            ->with(['profile', 'requisition.position', 'requisition.client', 'requisition.city'])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $inner) use ($q): void {
                    $inner->where('hired_document', 'like', "%{$q}%")
                        ->orWhere('hired_full_name', 'like', "%{$q}%")
                        ->orWhereHas('requisition', fn (Builder $r) => $r->where('code', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('moved_to_ficha_at')
            ->get();

        return view('areas.gestion_humana.archivo.index', [
            'entries' => $entries,
            'filters' => ['q' => $q],
            'canManage' => $this->canManage(),
            'canExportArchive' => $this->canExportArchive(),
            'importResult' => $importResult,
            'importHasFailures' => $importHasFailures,
        ]);
    }

    public function import(ImportEmployeeArchiveRequest $request): RedirectResponse
    {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        try {
            $stats = $this->importService->import($path);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()]);
        }

        $message = sprintf(
            'Importacion de archivo finalizada: %d empleados actualizados.',
            $stats['updated'],
        );

        if ($stats['empty_rows'] > 0) {
            $message .= sprintf(' %d filas sin cedula ignoradas.', $stats['empty_rows']);
        }

        if ($stats['skipped'] > 0) {
            $message .= sprintf(' %d filas omitidas o con error (revise el detalle).', $stats['skipped']);
        }

        if ($stats['errors'] !== []) {
            Log::warning('Importacion archivo empleados con errores por fila.', [
                'user_id' => $request->user()->id,
                'errors_count' => count($stats['errors']),
                'errors' => array_slice($stats['errors'], 0, 200),
            ]);
        }

        $importResult = $this->buildImportResultPayload(
            $request->user(),
            $stats,
            'employee_archive',
            'Archivo empleados',
            [
                'Actualizados' => $stats['updated'],
                'Omitidos / error' => $stats['skipped'],
                'Filas vacias' => $stats['empty_rows'],
            ],
            array_keys(array_merge(
                config('employee_ficha.import_columns', []),
                config('employee_ficha.archive_export_extra_columns', []),
            )),
            'reporte_importacion_archivo',
        );

        return redirect()
            ->route('gestion-humana.archivo.index')
            ->with('status', $message)
            ->with('import_result', $importResult);
    }

    public function downloadImportReport(Request $request, string $token): StreamedResponse
    {
        abort_unless($this->canManage(), 403);

        return $this->downloadImportFailureReport($request->user(), $token, 'employee_archive');
    }

    public function edit(PersonalRequisitionFichaEntry $fichaEntry): View
    {
        abort_unless($this->canManage(), 403);

        $fichaEntry->load(['profile', 'requisition.position', 'requisition.client']);

        if ($fichaEntry->moved_to_ficha_at === null) {
            abort(404);
        }

        return view('areas.gestion_humana.archivo.edit', [
            'entry' => $fichaEntry,
        ]);
    }

    public function update(UpdateEmployeeArchiveRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        if ($fichaEntry->moved_to_ficha_at === null) {
            abort(404);
        }

        DB::transaction(function () use ($request, $fichaEntry): void {
            $profile = $fichaEntry->profile;

            if ($profile === null) {
                $profile = new EmployeeFichaProfile([
                    'personal_requisition_ficha_entry_id' => $fichaEntry->id,
                    'document_number' => $fichaEntry->hired_document,
                    'full_name' => $fichaEntry->hired_full_name,
                    'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
                ]);
            }

            $profile->fill($request->validated());
            $profile->save();
        });

        return redirect()
            ->route('gestion-humana.archivo.index', array_filter([
                'q' => $request->string('q')->toString() ?: null,
            ]))
            ->with('status', 'Ubicacion de archivo actualizada correctamente.');
    }

    private function authorizeView(): void
    {
        abort_unless($this->archivoAccess->canView(auth()->user()), 403);
    }

    private function canManage(): bool
    {
        return $this->archivoAccess->canManage(auth()->user());
    }

    private function canExportArchive(): bool
    {
        return $this->archivoAccess->canExportArchiveTemplate(auth()->user());
    }
}

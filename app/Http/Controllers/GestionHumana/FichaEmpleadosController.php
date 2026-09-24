<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\EmployeeFichaArchiveTemplateExport;
use App\Exports\EmployeeFichaImportTemplateExport;
use App\Exports\PlantillaMasivosExport;
use App\Http\Controllers\Concerns\HandlesImportFailureReports;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ImportEmployeeFichaRequest;
use App\Http\Requests\GestionHumana\StoreManualEmployeeFichaRequest;
use App\Http\Requests\GestionHumana\TerminateEmployeeFichaRequest;
use App\Http\Requests\GestionHumana\UpdateEmployeeFichaProfileRequest;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\Access\ArchivoAccessService;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeCursoDocumentService;
use App\Services\GestionHumana\EmployeeCursoPendingService;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\EmployeeFichaCatalogService;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use App\Services\GestionHumana\EmployeeFichaEntryDatatableService;
use App\Services\GestionHumana\EmployeeFichaImportService;
use App\Services\GestionHumana\EmployeeFichaProfileCatalogSync;
use App\Services\GestionHumana\EmployeeFichaProfilePrefill;
use App\Services\GestionHumana\EmployeeTerminationFollowupService;
use App\Traits\HasFichaEmpleadosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FichaEmpleadosController extends Controller
{
    use HandlesImportFailureReports;
    use HasFichaEmpleadosTabs;

    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly ArchivoAccessService $archivoAccess,
        private readonly PlantillaMasivosExport $plantillaMasivosExport,
        private readonly EmployeeFichaImportTemplateExport $importTemplateExport,
        private readonly EmployeeFichaArchiveTemplateExport $archiveTemplateExport,
        private readonly EmployeeFichaImportService $importService,
        private readonly EmployeeFichaProfilePrefill $profilePrefill,
        private readonly EmployeeFichaCatalogService $catalogService,
        private readonly EmployeeFichaEmploymentPeriodService $employmentPeriodService,
        private readonly EmployeeFichaAuditLogService $auditLogService,
        private readonly EmployeeFichaProfileCatalogSync $profileCatalogSync,
        private readonly EmployeeFichaEntryDatatableService $entryDatatableService,
        private readonly EmployeeTerminationFollowupService $terminationFollowupService,
        private readonly EmployeeCursoDocumentService $cursoDocumentService,
        private readonly EmployeeCursoPendingService $cursoPendingService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $estado = $this->resolveEstadoFilter($request);
        [$employmentStatus, $employmentStatusMode] = $this->resolveEmploymentStatusFilter($request, $estado);

        $pendingCount = PersonalRequisitionFichaEntry::query()->pending()->count();

        return view('areas.gestion_humana.ficha-empleados.employees.index', [
            'datatableUrl' => route('gestion-humana.ficha-empleados.employees.datatable', $request->query()),
            'filters' => [
                'q' => $q,
                'estado' => $estado,
                'employment_status' => $employmentStatus,
                'employment_status_mode' => $employmentStatusMode,
            ],
            'employmentStatusLabels' => self::employmentStatusFilterLabels(),
            'pendingCount' => $pendingCount,
            'canManage' => $this->canManage(),
            'canTerminate' => $this->canTerminate(),
            'canExportArchive' => $this->canExportArchive(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $estado = $this->resolveEstadoFilter($request);
        [$employmentStatus] = $this->resolveEmploymentStatusFilter($request, $estado);

        $query = $this->entryListQuery($q, $estado, $employmentStatus);

        return $this->entryDatatableService->respond(
            $request,
            $query,
            $estado,
            $this->canManage(),
        );
    }

    public function exportExcel(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $fechaDesde = $request->date('fecha_desde')?->toDateString();
        $fechaHasta = $request->date('fecha_hasta')?->toDateString();
        $hasDateRange = $fechaDesde !== null && $fechaHasta !== null;

        $query = $this->entryListQuery($q, 'en_ficha')
            ->with(['profile', 'requisition.position', 'requisition.city', 'requisition.contractType', 'requisition.client']);

        if ($hasDateRange) {
            $query->hireDateBetween($fechaDesde, $fechaHasta);
        } else {
            $query->withActiveProfile();
        }

        $entries = $query->orderByDesc('created_at')->get();

        if ($entries->isEmpty()) {
            return redirect()
                ->route('gestion-humana.ficha-empleados.employees.index')
                ->withErrors(['export' => 'No hay empleados activos en ficha para exportar con los filtros seleccionados.']);
        }

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'masivos_excel',
            metadata: $this->masivosExportAuditMetadata($entries->count(), $hasDateRange, $fechaDesde, $fechaHasta),
        );

        return $this->plantillaMasivosExport->download(
            $entries,
            'plantilla_masivos_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless($this->canManage(), 403);

        return $this->importTemplateExport->download();
    }

    public function exportImportTemplate(Request $request): StreamedResponse|RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $q = trim($request->string('q')->toString());
        $fechaDesde = $request->date('fecha_desde')?->toDateString();
        $fechaHasta = $request->date('fecha_hasta')?->toDateString();
        $hasDateRange = $fechaDesde !== null && $fechaHasta !== null;

        $query = $this->entryListQuery($q, 'en_ficha')
            ->with(['profile', 'requisition.position', 'requisition.client']);

        if ($hasDateRange) {
            $query->hireDateBetween($fechaDesde, $fechaHasta);
        } else {
            $query->withActiveProfile();
        }

        $entries = $query->orderByDesc('created_at')->get();

        if ($entries->isEmpty()) {
            return redirect()
                ->route('gestion-humana.ficha-empleados.employees.index')
                ->withErrors(['export' => 'No hay empleados en ficha para exportar con los filtros seleccionados.']);
        }

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'import_template_data',
            metadata: ['row_count' => $entries->count()],
        );

        return $this->importTemplateExport->downloadWithData(
            $entries,
            'plantilla_importacion_datos_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportArchiveTemplate(Request $request): StreamedResponse|RedirectResponse
    {
        abort_unless($this->canExportArchive(), 403);

        $q = trim($request->string('q')->toString());
        $fechaDesde = $request->date('fecha_desde')?->toDateString();
        $fechaHasta = $request->date('fecha_hasta')?->toDateString();
        $hasDateRange = $fechaDesde !== null && $fechaHasta !== null;
        [$employmentStatus] = $this->resolveEmploymentStatusFilter($request, 'en_ficha');

        $query = $this->entryListQuery($q, 'en_ficha', $employmentStatus)
            ->with(['profile', 'requisition.position', 'requisition.client']);

        if ($hasDateRange) {
            $query->hireDateBetween($fechaDesde, $fechaHasta);
        }

        $entries = $query->orderByDesc('created_at')->get();

        if ($entries->isEmpty()) {
            return back()->withErrors([
                'export' => 'No hay empleados en ficha para exportar con los filtros seleccionados.',
            ]);
        }

        return $this->archiveTemplateExport->downloadWithData(
            $entries,
            'exportacion_archivo_empleados_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function import(ImportEmployeeFichaRequest $request): RedirectResponse
    {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        try {
            $stats = $this->importService->import($path, false, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()]);
        }

        $message = sprintf(
            'Importacion finalizada: %d nuevos, %d actualizados.',
            $stats['imported'],
            $stats['updated'],
        );

        if ($stats['empty_rows'] > 0) {
            $message .= sprintf(' %d filas sin cedula ignoradas.', $stats['empty_rows']);
        }

        if ($stats['skipped'] > 0) {
            $message .= sprintf(' %d filas con error (revise el detalle abajo).', $stats['skipped']);
        }

        if ($stats['errors'] !== []) {
            Log::warning('Importacion ficha empleados con errores por fila.', [
                'user_id' => $request->user()->id,
                'errors_count' => count($stats['errors']),
                'errors' => array_slice($stats['errors'], 0, 200),
            ]);
        }

        $importResult = $this->buildImportResultPayload(
            $request->user(),
            $stats,
            'employee_ficha',
            'Ficha empleados',
            [
                'Filas nuevas' => $stats['imported'],
                'Filas actualizadas' => $stats['updated'],
                'Filas con error' => $stats['skipped'],
                'Filas vacias' => $stats['empty_rows'],
            ],
            array_keys(config('employee_ficha.import_columns', [])),
            'reporte_importacion_ficha_empleados',
        );

        $errorsForSession = array_slice($importResult['errors'], 0, 100);
        $importResult['failed'] = $importResult['failures_count'];
        $importResult['errors'] = $errorsForSession;
        $importResult['errors_truncated'] = ($importResult['errors_total'] ?? 0) > count($errorsForSession);

        $this->auditLogService->logEvent(
            eventType: 'import',
            action: 'profiles',
            metadata: [
                'imported' => $stats['imported'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'empty_rows' => $stats['empty_rows'],
            ],
        );

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.index')
            ->with('status', $message)
            ->with('import_done', true)
            ->with('import_result', $importResult);
    }

    public function downloadImportReport(Request $request, string $token): StreamedResponse
    {
        abort_unless($this->canManage(), 403);

        return $this->downloadImportFailureReport($request->user(), $token, 'employee_ficha');
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManage(), 403);

        $fichaEntry = null;
        $profile = new EmployeeFichaProfile([
            'document_type' => 'C',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $desde = $request->query('desde');

        if ($desde !== null) {
            $fichaEntry = PersonalRequisitionFichaEntry::query()
                ->pending()
                ->with(['requisition.position', 'requisition.city', 'requisition.contractType', 'requisition.client', 'profile'])
                ->findOrFail($desde);

            $profile = $this->profilePrefill->buildForEntry($fichaEntry);
        }

        return view('areas.gestion_humana.ficha-empleados.employees.create-ficha', [
            'fichaEntry' => $fichaEntry,
            'profile' => $profile,
            'isRehire' => $fichaEntry?->isRehirePending() ?? false,
            'requisitionReference' => $fichaEntry?->requisition
                ? $this->profilePrefill->requisitionReferenceForEntry($fichaEntry)
                : null,
            'catalogs' => $this->catalogService->optionsForForms(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
        ]);
    }

    public function store(StoreManualEmployeeFichaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;
        $fichaEntryId = $validated['ficha_entry_id'] ?? null;

        if ($fichaEntryId !== null) {
            $entry = DB::transaction(function () use ($validated, $userId, $fichaEntryId): PersonalRequisitionFichaEntry {
                $entry = PersonalRequisitionFichaEntry::query()->pending()->findOrFail($fichaEntryId);

                $hiredDocument = trim($validated['hired_document']);
                $firstSurname = trim((string) ($validated['first_surname'] ?? $entry->first_surname));
                $secondSurname = isset($validated['second_surname']) ? trim((string) $validated['second_surname']) : $entry->second_surname;
                $firstName = trim((string) ($validated['first_name'] ?? $entry->first_name));
                $secondName = isset($validated['second_name']) ? trim((string) $validated['second_name']) : $entry->second_name;

                $nameParts = array_filter([$firstSurname, $secondSurname, $firstName, $secondName], fn ($v) => $v !== null && $v !== '');
                $computedFullName = $nameParts !== [] ? implode(' ', $nameParts) : trim((string) ($validated['hired_full_name'] ?? $entry->hired_full_name));

                $entry->update([
                    'hired_document' => $hiredDocument,
                    'first_surname' => $firstSurname ?: null,
                    'second_surname' => $secondSurname ?: null,
                    'first_name' => $firstName ?: null,
                    'second_name' => $secondName ?: null,
                    'hired_full_name' => $computedFullName,
                    'moved_to_ficha_at' => now(),
                    'moved_to_ficha_by' => $userId,
                ]);

                $profile = $entry->profile ?? new EmployeeFichaProfile(['personal_requisition_ficha_entry_id' => $entry->id]);

                $profileAttributes = $this->mergeProfilePayrollExtra(
                    $profile,
                    collect($validated)
                        ->except(['hired_document', 'hired_full_name', 'ficha_entry_id'])
                        ->merge([
                            'personal_requisition_ficha_entry_id' => $entry->id,
                            'document_number' => $hiredDocument,
                            'full_name' => $computedFullName,
                            'first_surname' => $firstSurname ?: null,
                            'second_surname' => $secondSurname ?: null,
                            'first_name' => $firstName ?: null,
                            'second_name' => $secondName ?: null,
                        ])
                        ->all(),
                );

                $profileAttributes = $this->mergeWorkCityFromRequisitionIfMissing($entry, $profileAttributes);

                $profile->fill($profileAttributes);
                $profile->employment_status = EmployeeFichaProfile::STATUS_ACTIVO;
                $profile->termination_date = null;
                $profile->save();

                $this->profileCatalogSync->syncAndSave($profile);

                $this->employmentPeriodService->openPeriod(
                    $entry,
                    $profile->fresh()->getAttributes(),
                    $userId,
                    $entry->personal_requisition_id,
                );
                $this->employmentPeriodService->syncProfileFromActivePeriod($entry, $profile)->save();

                return $entry->fresh(['requisition', 'profile']);
            });

            $this->enqueueCursoPendingIfEligible($entry, $userId);

            $isRehire = ($entry->employmentPeriods()->count() ?? 0) > 1;

            $metadata = [
                'hired_document' => $entry->hired_document,
                'source' => $isRehire ? 'rehire' : 'waiting_list',
            ];

            if ($entry->personal_requisition_id !== null) {
                $metadata['requisition_id'] = $entry->personal_requisition_id;
            }

            $this->auditLogService->logEvent(
                eventType: 'ficha_entry',
                action: $isRehire ? 'rehire' : 'promote',
                metadata: $metadata,
                model: $entry,
            );

            return redirect()
                ->route('gestion-humana.ficha-empleados.employees.index')
                ->with('status', $isRehire
                    ? 'Reingreso registrado correctamente.'
                    : 'Empleado movido a Ficha empleados correctamente.');
        }

        $entry = DB::transaction(function () use ($validated, $userId): PersonalRequisitionFichaEntry {
            $hiredDocument = trim($validated['hired_document']);
            $firstSurname = trim((string) ($validated['first_surname'] ?? ''));
            $secondSurname = isset($validated['second_surname']) ? trim((string) $validated['second_surname']) : null;
            $firstName = trim((string) ($validated['first_name'] ?? ''));
            $secondName = isset($validated['second_name']) ? trim((string) $validated['second_name']) : null;

            $nameParts = array_filter([$firstSurname, $secondSurname, $firstName, $secondName], fn ($v) => $v !== null && $v !== '');
            $computedFullName = $nameParts !== [] ? implode(' ', $nameParts) : trim((string) ($validated['hired_full_name'] ?? ''));

            $entry = PersonalRequisitionFichaEntry::query()->create([
                'personal_requisition_id' => null,
                'hired_document' => $hiredDocument,
                'first_surname' => $firstSurname ?: null,
                'second_surname' => $secondSurname ?: null,
                'first_name' => $firstName ?: null,
                'second_name' => $secondName ?: null,
                'hired_full_name' => $computedFullName,
                'moved_to_ficha_at' => now(),
                'moved_to_ficha_by' => $userId,
                'created_by' => $userId,
            ]);

            $profileAttributes = collect($validated)
                ->except(['hired_document', 'hired_full_name', 'ficha_entry_id'])
                ->merge([
                    'personal_requisition_ficha_entry_id' => $entry->id,
                    'document_number' => $hiredDocument,
                    'full_name' => $computedFullName,
                    'first_surname' => $firstSurname ?: null,
                    'second_surname' => $secondSurname ?: null,
                    'first_name' => $firstName ?: null,
                    'second_name' => $secondName ?: null,
                    'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
                ])
                ->all();

            $profile = EmployeeFichaProfile::query()->create($profileAttributes);
            $this->profileCatalogSync->syncAndSave($profile);

            $this->employmentPeriodService->openPeriod(
                $entry,
                $profile->fresh()->getAttributes(),
                $userId,
                null,
            );
            $this->employmentPeriodService->syncProfileFromActivePeriod($entry, $profile)->save();

            return $entry->fresh(['profile']);
        });

        $this->enqueueCursoPendingIfEligible($entry, $userId);

        $this->auditLogService->logEvent(
            eventType: 'ficha_entry',
            action: 'create',
            metadata: [
                'hired_document' => $entry->hired_document,
                'source' => 'manual',
            ],
            model: $entry,
        );

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry)
            ->with('status', 'Empleado creado en ficha correctamente.');
    }

    public function editFicha(PersonalRequisitionFichaEntry $fichaEntry): View
    {
        abort_unless($this->canManage(), 403);

        $fichaEntry->load(['requisition.position', 'requisition.city', 'requisition.client', 'requisition.contractType', 'profile', 'activeEmploymentPeriod']);
        $profile = $fichaEntry->profile ?? $this->profilePrefill->prefillForEntry($fichaEntry);
        $this->profilePrefill->ensureWorkCityFromRequisition($fichaEntry, $profile);
        $profile->refresh();
        $activePeriod = $this->employmentPeriodService->activePeriod($fichaEntry);
        $employmentHistory = $this->employmentPeriodService->historyForEntry($fichaEntry);
        $letterPeriod = $this->resolveLetterPeriod($employmentHistory, $profile);
        $documentNumber = $this->resolveEntryDocumentNumber($fichaEntry, $profile);
        $employeeCursos = $this->employeeCursosForDocumentNumber($documentNumber);

        return view('areas.gestion_humana.ficha-empleados.employees.edit-ficha', [
            'entry' => $fichaEntry,
            'profile' => $profile->fresh(),
            'requisitionReference' => $fichaEntry->requisition
                ? $this->profilePrefill->requisitionReferenceForEntry($fichaEntry)
                : null,
            'activePeriod' => $activePeriod,
            'employmentHistory' => $employmentHistory,
            'letterPeriod' => $letterPeriod,
            'canGenerateLetters' => $this->canGenerateLetters($letterPeriod),
            'canGenerateContratacionLetters' => $this->canGenerateContratacionLetters($activePeriod),
            'canTerminate' => $this->canTerminate() && $activePeriod !== null,
            'catalogs' => $this->catalogService->optionsForForms(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
            'employeeCursos' => $employeeCursos,
            'canViewEmployeeCursos' => $this->fichaEmpleadosAccess->canView(auth()->user()),
        ]);
    }

    public function employeeCursos(PersonalRequisitionFichaEntry $fichaEntry): JsonResponse
    {
        abort_unless($this->fichaEmpleadosAccess->canView(auth()->user()), 403);

        $fichaEntry->loadMissing('profile');
        $documentNumber = $this->resolveEntryDocumentNumber($fichaEntry, $fichaEntry->profile);
        $cursos = $this->employeeCursosForDocumentNumber($documentNumber);

        return response()->json([
            'document_number' => $documentNumber,
            'data' => $cursos->map(fn (EmployeeCurso $curso): array => [
                'id' => $curso->id,
                'tipo_curso' => $curso->cursoTipo?->tipo_curso,
                'fecha_expedicion' => optional($curso->fecha_expedicion)?->format('Y-m-d'),
                'numero_curso' => $curso->numero_curso,
                'vigencia' => $curso->computeVigencia(),
                'estado' => $curso->estado,
                'has_document' => $curso->hasDocument(),
                'document_original_name' => $curso->document_original_name,
                'document_url' => $curso->hasDocument()
                    ? route('gestion-humana.ficha-empleados.employees.cursos.document', [$fichaEntry, $curso])
                    : null,
            ])->values(),
        ]);
    }

    public function downloadEmployeeCursoDocument(
        PersonalRequisitionFichaEntry $fichaEntry,
        EmployeeCurso $employeeCurso,
    ): StreamedResponse|Response {
        abort_unless($this->fichaEmpleadosAccess->canView(auth()->user()), 403);

        $fichaEntry->loadMissing('profile');
        $documentNumber = $this->resolveEntryDocumentNumber($fichaEntry, $fichaEntry->profile);

        abort_unless(
            $documentNumber !== '' && hash_equals($documentNumber, (string) $employeeCurso->document_number),
            404,
        );
        abort_unless($employeeCurso->hasDocument(), 404);

        $disk = Storage::disk($this->cursoDocumentService->disk());
        $path = (string) $employeeCurso->document_path;
        abort_unless($disk->exists($path), 404);

        return $disk->download(
            $path,
            $employeeCurso->document_original_name ?: basename($path),
        );
    }

    public function updateFicha(UpdateEmployeeFichaProfileRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        $fichaEntry->load('profile');
        $profile = $fichaEntry->profile ?? $this->profilePrefill->prefillForEntry($fichaEntry);
        $before = $this->profileAuditSnapshot($profile);

        $validated = $request->validated();
        $attributes = $this->mergeProfilePayrollExtra($profile, $validated);
        $attributes['phone_secondary'] = $request->input('phone_secondary');
        $attributes = $this->mergeWorkCityFromRequisitionIfMissing($fichaEntry, $attributes);

        $firstSurname = trim((string) ($attributes['first_surname'] ?? $profile->first_surname));
        $secondSurname = array_key_exists('second_surname', $attributes) ? trim((string) $attributes['second_surname']) : $profile->second_surname;
        $firstName = trim((string) ($attributes['first_name'] ?? $profile->first_name));
        $secondName = array_key_exists('second_name', $attributes) ? trim((string) $attributes['second_name']) : $profile->second_name;

        $nameParts = array_filter([$firstSurname, $secondSurname, $firstName, $secondName], fn ($v) => $v !== null && $v !== '');
        $computedFullName = $nameParts !== [] ? implode(' ', $nameParts) : $profile->full_name;
        $attributes['full_name'] = $computedFullName;

        $fichaEntry->update([
            'first_surname' => $firstSurname ?: null,
            'second_surname' => $secondSurname ?: null,
            'first_name' => $firstName ?: null,
            'second_name' => $secondName ?: null,
            'hired_full_name' => $computedFullName ?: $fichaEntry->hired_full_name,
        ]);

        $profile->fill($attributes);
        $profile->save();

        $this->profileCatalogSync->syncAndSave($profile);
        $this->employmentPeriodService->syncActivePeriodFromProfileAttributes(
            $fichaEntry,
            $profile->getAttributes(),
            (int) $request->user()->id,
        );
        $this->employmentPeriodService->syncProfileFromActivePeriod($fichaEntry, $profile)->save();

        // La fecha de desvinculación vive en el perfil (import / corrección); el sync de periodo
        // activo puede limpiarla — se reaplica desde el formulario y se alinea el estado.
        if (array_key_exists('termination_date', $validated)) {
            $profile->termination_date = $validated['termination_date'];
            $profile->syncEmploymentStatusFromTerminationDate();
            $profile->save();
        }

        $profile->refresh();
        $after = $this->profileAuditSnapshot($profile);

        if ($before['employment_status'] !== $after['employment_status']) {
            $this->auditLogService->logModelChange(
                eventType: 'ficha_profile',
                action: 'status_change',
                model: $profile,
                before: ['employment_status' => $before['employment_status']],
                after: ['employment_status' => $after['employment_status']],
                metadata: ['document_number' => $profile->document_number],
            );
        } else {
            [$oldValues, $newValues] = $this->diffProfileAuditFields($before, $after);

            if ($oldValues !== []) {
                $this->auditLogService->logModelChange(
                    eventType: 'ficha_profile',
                    action: 'update',
                    model: $profile,
                    before: $oldValues,
                    after: $newValues,
                );
            }
        }

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.ficha.edit', $fichaEntry)
            ->with('status', 'Ficha de empleado actualizada.');
    }

    public function terminate(TerminateEmployeeFichaRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        abort_unless($this->canTerminate(), 403);

        $fichaEntry->load('profile');
        $beforeStatus = $fichaEntry->profile?->employment_status;

        DB::transaction(function () use ($request, $fichaEntry): void {
            $closedPeriod = $this->employmentPeriodService->closeActivePeriod(
                $fichaEntry,
                $request->validated(),
                (int) $request->user()->id,
            );
            $this->employmentPeriodService->syncProfileAfterTermination($fichaEntry);
            $this->terminationFollowupService->ensureForClosedPeriod(
                $closedPeriod,
                $fichaEntry->fresh(),
                (int) $request->user()->id,
            );
        });

        $fichaEntry->load('profile');

        if ($beforeStatus !== EmployeeFichaProfile::STATUS_DESVINCULADO) {
            $this->auditLogService->logModelChange(
                eventType: 'ficha_profile',
                action: 'status_change',
                model: $fichaEntry->profile,
                before: ['employment_status' => $beforeStatus ?? EmployeeFichaProfile::STATUS_ACTIVO],
                after: ['employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO],
                metadata: ['document_number' => $fichaEntry->hired_document],
            );
        }

        $this->auditLogService->logEvent(
            eventType: 'employment_period',
            action: 'close',
            metadata: [
                'document_number' => $fichaEntry->hired_document,
                'termination_cause_code' => $request->validated('termination_cause_code'),
                'is_rehireable' => (bool) $request->validated('is_rehireable'),
            ],
            model: $fichaEntry,
        );

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.ficha.edit', $fichaEntry)
            ->with('status', 'Desvinculacion registrada correctamente.');
    }

    /**
     * @return array<string, string>
     */
    private static function estadoFilterLabels(): array
    {
        return [
            'pendientes' => 'Pendientes',
            'en_ficha' => 'En ficha',
        ];
    }

    private function resolveEstadoFilter(Request $request): string
    {
        $estado = trim($request->string('estado')->toString());

        return array_key_exists($estado, self::estadoFilterLabels()) ? $estado : 'en_ficha';
    }

    /**
     * @return array<string, string>
     */
    private static function employmentStatusFilterLabels(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('employee_ficha.employment_status', []);

        return $labels;
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function resolveEmploymentStatusFilter(Request $request, string $estado): array
    {
        if ($estado !== 'en_ficha') {
            return [null, 'none'];
        }

        if (! $request->has('employment_status')) {
            return [EmployeeFichaProfile::STATUS_ACTIVO, 'default_activo'];
        }

        $status = trim($request->string('employment_status')->toString());

        if ($status === '' || $status === 'todos') {
            return [null, 'todos'];
        }

        if (array_key_exists($status, self::employmentStatusFilterLabels())) {
            return [$status, $status];
        }

        return [EmployeeFichaProfile::STATUS_ACTIVO, 'default_activo'];
    }

    /**
     * @return Builder<PersonalRequisitionFichaEntry>
     */
    private function entryListQuery(string $q, string $estado, ?string $employmentStatus = null): Builder
    {
        return PersonalRequisitionFichaEntry::query()
            ->with(['requisition.position', 'requisition.client', 'requisition.city', 'movedBy', 'profile'])
            ->when(
                $estado === 'en_ficha',
                fn (Builder $query) => $query->inFicha(),
                fn (Builder $query) => $query->pending()
            )
            ->when(
                $estado === 'en_ficha' && $employmentStatus !== null,
                fn (Builder $query) => $query->withEmploymentStatus($employmentStatus)
            )
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $inner) use ($q): void {
                    $inner->where('hired_document', 'like', "%{$q}%")
                        ->orWhere('hired_full_name', 'like', "%{$q}%")
                        ->orWhereHas('requisition', fn (Builder $r) => $r->where('code', 'like', "%{$q}%"));
                });
            });
    }

    private function authorizeView(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canView(auth()->user()), 403);
    }

    /**
     * Encola en «Nuevos sin curso» tras ingreso a ficha (post go-live). Idempotente.
     */
    private function enqueueCursoPendingIfEligible(PersonalRequisitionFichaEntry $entry, int $userId): void
    {
        $profile = $entry->profile;
        $documentNumber = trim((string) ($profile?->document_number ?? $entry->hired_document ?? ''));

        if ($documentNumber === '') {
            return;
        }

        $this->cursoPendingService->enqueueIfEligible([
            'document_number' => $documentNumber,
            'full_name' => $profile?->full_name ?? $entry->hired_full_name,
            'employee_ficha_profile_id' => $profile?->id,
            'personal_requisition_ficha_entry_id' => $entry->id,
            'enqueued_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function mergeProfilePayrollExtra(EmployeeFichaProfile $profile, array $attributes): array
    {
        $existing = is_array($profile->payroll_extra) ? $profile->payroll_extra : [];

        if (isset($attributes['payroll_extra']) && is_array($attributes['payroll_extra'])) {
            $attributes['payroll_extra'] = array_merge($existing, $attributes['payroll_extra']);
        } elseif (array_key_exists('birth_date', $attributes)) {
            $attributes['payroll_extra'] = $existing;
        }

        return EmployeeFichaProfile::syncAgeAttributesFromBirthDate($attributes);
    }

    private function canManage(): bool
    {
        return $this->fichaEmpleadosAccess->canManage(auth()->user());
    }

    private function resolveEntryDocumentNumber(
        PersonalRequisitionFichaEntry $fichaEntry,
        ?EmployeeFichaProfile $profile = null,
    ): string {
        $fromProfile = trim((string) ($profile?->document_number ?? ''));
        if ($fromProfile !== '') {
            return $fromProfile;
        }

        return trim((string) ($fichaEntry->hired_document ?? ''));
    }

    /**
     * @return Collection<int, EmployeeCurso>
     */
    private function employeeCursosForDocumentNumber(string $documentNumber): Collection
    {
        if ($documentNumber === '') {
            return collect();
        }

        return EmployeeCurso::query()
            ->with('cursoTipo')
            ->where('document_number', $documentNumber)
            ->orderByDesc('fecha_expedicion')
            ->orderByDesc('id')
            ->get();
    }

    private function canTerminate(): bool
    {
        return $this->fichaEmpleadosAccess->canTerminate(auth()->user());
    }

    /**
     * @param  Collection<int, EmployeeFichaEmploymentPeriod>  $employmentHistory
     */
    private function resolveLetterPeriod(Collection $employmentHistory, EmployeeFichaProfile $profile): ?EmployeeFichaEmploymentPeriod
    {
        if ($profile->employment_status !== EmployeeFichaProfile::STATUS_DESVINCULADO) {
            return null;
        }

        return $employmentHistory
            ->first(function (EmployeeFichaEmploymentPeriod $period): bool {
                return $period->status === EmployeeFichaEmploymentPeriod::STATUS_CERRADO;
            });
    }

    private function canGenerateLetters(?EmployeeFichaEmploymentPeriod $letterPeriod): bool
    {
        return $this->canTerminate()
            && $letterPeriod !== null
            && $letterPeriod->status === EmployeeFichaEmploymentPeriod::STATUS_CERRADO;
    }

    private function canGenerateContratacionLetters(?EmployeeFichaEmploymentPeriod $activePeriod): bool
    {
        return $this->canManage()
            && $activePeriod !== null
            && $activePeriod->status === EmployeeFichaEmploymentPeriod::STATUS_ACTIVO;
    }

    private function canExportArchive(): bool
    {
        return $this->archivoAccess->canExportArchiveTemplate(auth()->user());
    }

    /**
     * @return array<string, mixed>
     */
    private function profileAuditSnapshot(EmployeeFichaProfile $profile): array
    {
        return [
            'document_number' => $profile->document_number,
            'employment_status' => $profile->employment_status,
            'hire_date' => $profile->hire_date?->toDateString(),
            'termination_date' => $profile->termination_date?->toDateString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function diffProfileAuditFields(array $before, array $after): array
    {
        $oldValues = [];
        $newValues = [];

        foreach ($before as $field => $beforeValue) {
            if ($field === 'employment_status') {
                continue;
            }

            $afterValue = $after[$field] ?? null;

            if ($beforeValue !== $afterValue) {
                $oldValues[$field] = $beforeValue;
                $newValues[$field] = $afterValue;
            }
        }

        return [$oldValues, $newValues];
    }

    /**
     * Si el formulario no envio ciudad de trabajo, completa desde requisition.city_id.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function mergeWorkCityFromRequisitionIfMissing(
        PersonalRequisitionFichaEntry $entry,
        array $attributes,
    ): array {
        if (filled($attributes['work_city_code'] ?? null) || filled($attributes['work_city_name'] ?? null)) {
            return $attributes;
        }

        $fromRequisition = $this->profilePrefill->workCityAttributesFromEntry($entry);

        if ($fromRequisition['work_city_code'] === null && $fromRequisition['work_city_name'] === null) {
            return $attributes;
        }

        return array_merge($attributes, $fromRequisition);
    }

    /**
     * @return array<string, mixed>
     */
    private function masivosExportAuditMetadata(
        int $rowCount,
        bool $hasDateRange,
        ?string $fechaDesde,
        ?string $fechaHasta,
    ): array {
        $metadata = ['row_count' => $rowCount];

        if ($hasDateRange && $fechaDesde !== null && $fechaHasta !== null) {
            $metadata['date_range'] = [
                'from' => $fechaDesde,
                'to' => $fechaHasta,
            ];
        }

        return $metadata;
    }
}

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
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\Access\ArchivoAccessService;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\EmployeeFichaCatalogService;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use App\Services\GestionHumana\EmployeeFichaImportService;
use App\Services\GestionHumana\EmployeeFichaNameParser;
use App\Services\GestionHumana\EmployeeFichaProfilePrefill;
use App\Traits\HasFichaEmpleadosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $estado = $this->resolveEstadoFilter($request);
        [$employmentStatus, $employmentStatusMode] = $this->resolveEmploymentStatusFilter($request, $estado);

        $entries = $this->entryListQuery($q, $estado, $employmentStatus)->get();
        $pendingCount = PersonalRequisitionFichaEntry::query()->pending()->count();

        return view('areas.gestion_humana.ficha-empleados.employees.index', [
            'entries' => $entries,
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

        $entries = $query->get();

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

        $entries = $query->get();

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

        $query = $this->entryListQuery($q, 'en_ficha')
            ->with(['profile', 'requisition.position', 'requisition.client']);

        if ($hasDateRange) {
            $query->hireDateBetween($fechaDesde, $fechaHasta);
        } else {
            $query->withActiveProfile();
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            return redirect()
                ->route('gestion-humana.ficha-empleados.employees.index')
                ->withErrors(['export' => 'No hay empleados en ficha para exportar con los filtros seleccionados.']);
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
                $hiredFullName = trim($validated['hired_full_name']);
                $parsed = EmployeeFichaNameParser::parse($hiredFullName);

                $entry->update([
                    'hired_document' => $hiredDocument,
                    'hired_full_name' => $hiredFullName,
                    'moved_to_ficha_at' => now(),
                    'moved_to_ficha_by' => $userId,
                ]);

                $profileAttributes = collect($validated)
                    ->except(['hired_document', 'hired_full_name', 'ficha_entry_id'])
                    ->merge([
                        'personal_requisition_ficha_entry_id' => $entry->id,
                        'document_number' => $hiredDocument,
                        'full_name' => $parsed['full_name'] ?: $hiredFullName,
                        'first_surname' => $parsed['first_surname'],
                        'second_surname' => $parsed['second_surname'],
                        'first_name' => $parsed['first_name'],
                        'second_name' => $parsed['second_name'],
                    ])
                    ->all();

                $profile = $entry->profile ?? new EmployeeFichaProfile(['personal_requisition_ficha_entry_id' => $entry->id]);
                $profile->fill($profileAttributes);
                $profile->employment_status = EmployeeFichaProfile::STATUS_ACTIVO;
                $profile->termination_date = null;
                $profile->save();

                $this->syncCatalogNamesFromCodes($profile);

                $this->employmentPeriodService->openPeriod(
                    $entry,
                    $profileAttributes,
                    $userId,
                    $entry->personal_requisition_id,
                );
                $this->employmentPeriodService->syncProfileFromActivePeriod($entry, $profile)->save();

                return $entry->fresh(['requisition']);
            });

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
            $hiredFullName = trim($validated['hired_full_name']);
            $parsed = EmployeeFichaNameParser::parse($hiredFullName);

            $entry = PersonalRequisitionFichaEntry::query()->create([
                'personal_requisition_id' => null,
                'hired_document' => $hiredDocument,
                'hired_full_name' => $hiredFullName,
                'moved_to_ficha_at' => now(),
                'moved_to_ficha_by' => $userId,
                'created_by' => $userId,
            ]);

            $profileAttributes = collect($validated)
                ->except(['hired_document', 'hired_full_name', 'ficha_entry_id'])
                ->merge([
                    'personal_requisition_ficha_entry_id' => $entry->id,
                    'document_number' => $hiredDocument,
                    'full_name' => $parsed['full_name'] ?: $hiredFullName,
                    'first_surname' => $parsed['first_surname'],
                    'second_surname' => $parsed['second_surname'],
                    'first_name' => $parsed['first_name'],
                    'second_name' => $parsed['second_name'],
                    'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
                ])
                ->all();

            $profile = EmployeeFichaProfile::query()->create($profileAttributes);
            $this->syncCatalogNamesFromCodes($profile);

            $this->employmentPeriodService->openPeriod(
                $entry,
                $profileAttributes,
                $userId,
                null,
            );
            $this->employmentPeriodService->syncProfileFromActivePeriod($entry, $profile)->save();

            return $entry;
        });

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

        $fichaEntry->load(['requisition.position', 'requisition.city', 'profile', 'activeEmploymentPeriod']);
        $profile = $fichaEntry->profile ?? $this->profilePrefill->prefillForEntry($fichaEntry);
        $activePeriod = $this->employmentPeriodService->activePeriod($fichaEntry);
        $employmentHistory = $this->employmentPeriodService->historyForEntry($fichaEntry);
        $letterPeriod = $this->resolveLetterPeriod($employmentHistory, $profile);

        return view('areas.gestion_humana.ficha-empleados.employees.edit-ficha', [
            'entry' => $fichaEntry,
            'profile' => $profile->fresh(),
            'activePeriod' => $activePeriod,
            'employmentHistory' => $employmentHistory,
            'letterPeriod' => $letterPeriod,
            'canGenerateLetters' => $this->canGenerateLetters($letterPeriod),
            'canTerminate' => $this->canTerminate() && $activePeriod !== null,
            'catalogs' => $this->catalogService->optionsForForms(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
        ]);
    }

    public function updateFicha(UpdateEmployeeFichaProfileRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        $fichaEntry->load('profile');
        $profile = $fichaEntry->profile ?? $this->profilePrefill->prefillForEntry($fichaEntry);
        $before = $this->profileAuditSnapshot($profile);

        $attributes = $request->validated();
        $attributes['phone_secondary'] = $request->input('phone_secondary');

        $profile->fill($attributes);
        $profile->save();

        $this->syncCatalogNamesFromCodes($profile);
        $this->employmentPeriodService->syncActivePeriodFromProfileAttributes(
            $fichaEntry,
            $profile->getAttributes(),
            (int) $request->user()->id,
        );
        $this->employmentPeriodService->syncProfileFromActivePeriod($fichaEntry, $profile)->save();

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
            $this->employmentPeriodService->closeActivePeriod(
                $fichaEntry,
                $request->validated(),
                (int) $request->user()->id,
            );
            $this->employmentPeriodService->syncProfileAfterTermination($fichaEntry);
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
            })
            ->orderByDesc('created_at');
    }

    private function syncCatalogNamesFromCodes(EmployeeFichaProfile $profile): void
    {
        $map = [
            'eps_code' => 'eps_name',
            'afp_code' => 'afp_name',
            'position_code' => 'position_name',
            'cost_center_code' => 'cost_center_name',
            'bank_code' => 'bank_name',
            'economic_activity_code' => 'economic_activity_name',
            'residence_city_code' => 'residence_city_name',
        ];

        foreach ($map as $codeField => $nameField) {
            $code = $profile->{$codeField};
            if ($code === null || $code === '') {
                continue;
            }

            $catalogType = match ($codeField) {
                'eps_code' => 'eps',
                'afp_code' => 'afp',
                'position_code' => 'position',
                'cost_center_code' => 'cost_center',
                'bank_code' => 'bank',
                'economic_activity_code' => 'economic_activity',
                default => 'city',
            };

            $name = PayrollCatalogItem::query()
                ->ofType($catalogType)
                ->where('code', $code)
                ->value('name');

            if ($name !== null) {
                $profile->{$nameField} = $name;
            }
        }

        $profile->save();
    }

    private function authorizeView(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canView(auth()->user()), 403);
    }

    private function canManage(): bool
    {
        return $this->fichaEmpleadosAccess->canManage(auth()->user());
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

        $supportedCauses = config('employee_ficha.termination_letter_supported_causes', []);

        return $employmentHistory
            ->first(function (EmployeeFichaEmploymentPeriod $period) use ($supportedCauses): bool {
                return $period->status === EmployeeFichaEmploymentPeriod::STATUS_CERRADO
                    && in_array((string) $period->termination_cause_code, $supportedCauses, true);
            });
    }

    private function canGenerateLetters(?EmployeeFichaEmploymentPeriod $letterPeriod): bool
    {
        return $this->canTerminate() && $letterPeriod !== null;
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

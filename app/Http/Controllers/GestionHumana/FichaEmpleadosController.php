<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\EmployeeFichaImportTemplateExport;
use App\Exports\PlantillaMasivosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ImportEmployeeFichaRequest;
use App\Http\Requests\GestionHumana\PromoteFichaEntryRequest;
use App\Http\Requests\GestionHumana\StoreManualEmployeeFichaRequest;
use App\Http\Requests\GestionHumana\UpdateEmployeeFichaProfileRequest;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaCatalogService;
use App\Services\GestionHumana\EmployeeFichaImportService;
use App\Services\GestionHumana\EmployeeFichaNameParser;
use App\Services\GestionHumana\EmployeeFichaProfilePrefill;
use App\Traits\HasFichaEmpleadosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FichaEmpleadosController extends Controller
{
    use HasFichaEmpleadosTabs;

    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly PlantillaMasivosExport $plantillaMasivosExport,
        private readonly EmployeeFichaImportTemplateExport $importTemplateExport,
        private readonly EmployeeFichaImportService $importService,
        private readonly EmployeeFichaProfilePrefill $profilePrefill,
        private readonly EmployeeFichaCatalogService $catalogService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $estado = $this->resolveEstadoFilter($request);
        $employmentStatus = $this->resolveEmploymentStatusFilter($request, $estado);

        $entries = $this->entryListQuery($q, $estado, $employmentStatus)->get();
        $pendingCount = PersonalRequisitionFichaEntry::query()->pending()->count();

        return view('areas.gestion_humana.ficha-empleados.employees.index', [
            'entries' => $entries,
            'filters' => [
                'q' => $q,
                'estado' => $estado,
                'employment_status' => $employmentStatus,
            ],
            'employmentStatusLabels' => self::employmentStatusFilterLabels(),
            'pendingCount' => $pendingCount,
            'canManage' => $this->canManage(),
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

        return $this->importTemplateExport->downloadWithData(
            $entries,
            'plantilla_importacion_datos_'.now()->format('Y-m-d').'.xlsx'
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

        $errorsForSession = array_slice($stats['errors'], 0, 100);

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.index')
            ->with('status', $message)
            ->with('import_result', [
                'imported' => $stats['imported'],
                'updated' => $stats['updated'],
                'failed' => $stats['skipped'],
                'empty_rows' => $stats['empty_rows'],
                'errors' => $errorsForSession,
                'errors_total' => count($stats['errors']),
                'errors_truncated' => count($stats['errors']) > count($errorsForSession),
            ]);
    }

    public function create(): View
    {
        abort_unless($this->canManage(), 403);

        return view('areas.gestion_humana.ficha-empleados.employees.create-ficha', [
            'profile' => new EmployeeFichaProfile([
                'document_type' => 'C',
                'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            ]),
            'catalogs' => $this->catalogService->optionsForForms(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
        ]);
    }

    public function store(StoreManualEmployeeFichaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;

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
                ->except(['hired_document', 'hired_full_name'])
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
            $profile->syncEmploymentStatusFromTerminationDate();
            $profile->save();

            $this->syncCatalogNamesFromCodes($profile);

            return $entry;
        });

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry)
            ->with('status', 'Empleado creado en ficha correctamente.');
    }

    public function editFicha(PersonalRequisitionFichaEntry $fichaEntry): View
    {
        abort_unless($this->canManage(), 403);

        $fichaEntry->load(['requisition.position', 'requisition.city', 'profile']);
        $profile = $fichaEntry->profile ?? $this->profilePrefill->prefillForEntry($fichaEntry);

        return view('areas.gestion_humana.ficha-empleados.employees.edit-ficha', [
            'entry' => $fichaEntry,
            'profile' => $profile->fresh(),
            'catalogs' => $this->catalogService->optionsForForms(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
        ]);
    }

    public function updateFicha(UpdateEmployeeFichaProfileRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        $fichaEntry->load('profile');
        $profile = $fichaEntry->profile ?? $this->profilePrefill->prefillForEntry($fichaEntry);

        $attributes = $request->validated();
        $attributes['phone_secondary'] = $request->input('phone_secondary');

        $profile->fill($attributes);
        $profile->syncEmploymentStatusFromTerminationDate();
        $profile->save();

        $this->syncCatalogNamesFromCodes($profile);

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.ficha.edit', $fichaEntry)
            ->with('status', 'Ficha de empleado actualizada.');
    }

    public function promote(PromoteFichaEntryRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        if ($fichaEntry->moved_to_ficha_at !== null) {
            return redirect()
                ->route('gestion-humana.ficha-empleados.employees.index')
                ->with('status', 'Ese registro ya estaba en Ficha empleados.');
        }

        $fichaEntry->update([
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $request->user()->id,
        ]);

        $this->profilePrefill->prefillForEntry($fichaEntry->fresh());

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.index')
            ->with('status', 'Registro agregado a Ficha empleados.');
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

    private function resolveEmploymentStatusFilter(Request $request, string $estado): ?string
    {
        if ($estado !== 'en_ficha') {
            return null;
        }

        $status = trim($request->string('employment_status')->toString());

        if ($status === '') {
            return null;
        }

        return array_key_exists($status, self::employmentStatusFilterLabels()) ? $status : null;
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
}

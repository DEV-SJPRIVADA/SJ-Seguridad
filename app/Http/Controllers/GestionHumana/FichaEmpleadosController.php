<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\EmployeeFichaImportTemplateExport;
use App\Exports\PlantillaMasivosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ImportEmployeeFichaRequest;
use App\Http\Requests\GestionHumana\PromoteFichaEntryRequest;
use App\Http\Requests\GestionHumana\UpdateEmployeeFichaProfileRequest;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaImportService;
use App\Services\GestionHumana\EmployeeFichaProfilePrefill;
use App\Traits\HasFichaEmpleadosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $estado = $this->resolveEstadoFilter($request);

        $entries = $this->entryListQuery($q, $estado)->get();

        return view('areas.gestion_humana.ficha-empleados.employees.index', [
            'entries' => $entries,
            'filters' => ['q' => $q, 'estado' => $estado],
            'estadoLabels' => self::estadoFilterLabels(),
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
                ->route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'en_ficha'])
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
            'Importacion finalizada: %d nuevos, %d actualizados, %d omitidos.',
            $stats['imported'],
            $stats['updated'],
            $stats['skipped']
        );

        if ($stats['errors'] !== []) {
            $message .= ' Revise los errores en el log.';
        }

        return redirect()
            ->route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'en_ficha'])
            ->with('status', $message);
    }

    public function editFicha(PersonalRequisitionFichaEntry $fichaEntry): View
    {
        abort_unless($this->canManage(), 403);

        $fichaEntry->load(['requisition.position', 'requisition.city', 'profile']);
        $profile = $fichaEntry->profile ?? $this->profilePrefill->prefillForEntry($fichaEntry);

        return view('areas.gestion_humana.ficha-empleados.employees.edit-ficha', [
            'entry' => $fichaEntry,
            'profile' => $profile->fresh(),
            'catalogs' => $this->catalogOptions(),
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

        return array_key_exists($estado, self::estadoFilterLabels()) ? $estado : 'pendientes';
    }

    /**
     * @return Builder<PersonalRequisitionFichaEntry>
     */
    private function entryListQuery(string $q, string $estado): Builder
    {
        return PersonalRequisitionFichaEntry::query()
            ->with(['requisition.position', 'requisition.client', 'requisition.city', 'movedBy', 'profile'])
            ->when(
                $estado === 'en_ficha',
                fn (Builder $query) => $query->inFicha(),
                fn (Builder $query) => $query->pending()
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

    /**
     * @return array<string, list<array{code: string, name: string}>>
     */
    private function catalogOptions(): array
    {
        $options = [];

        foreach (config('employee_ficha.catalog_types', []) as $type) {
            $options[$type] = PayrollCatalogItem::query()
                ->ofType($type)
                ->active()
                ->orderBy('name')
                ->get(['code', 'name'])
                ->map(fn (PayrollCatalogItem $item): array => ['code' => $item->code, 'name' => $item->name])
                ->all();
        }

        return $options;
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

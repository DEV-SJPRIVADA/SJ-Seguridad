<?php

namespace App\Http\Controllers\Calidad;

use App\Exports\MtGi11Export;
use App\Exports\MtGi11ImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calidad\MtGi11\ImportMtGi11Request;
use App\Http\Requests\Calidad\MtGi11\StoreMtGi11AdjuntosRequest;
use App\Http\Requests\Calidad\MtGi11\StoreMtGi11CatalogItemRequest;
use App\Http\Requests\Calidad\MtGi11\StoreMtGi11SolicitudRequest;
use App\Http\Requests\Calidad\MtGi11\UpdateMtGi11CatalogItemRequest;
use App\Http\Requests\Calidad\MtGi11\UpdateMtGi11SolicitudRequest;
use App\Models\Calidad\MtGi11Adjunto;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Solicitud;
use App\Services\Access\MtGi11AccessService;
use App\Services\Calidad\MtGi11AdjuntoService;
use App\Services\Calidad\MtGi11AuditLogService;
use App\Services\Calidad\MtGi11CatalogService;
use App\Services\Calidad\MtGi11DashboardService;
use App\Services\Calidad\MtGi11DatatableService;
use App\Services\Calidad\MtGi11ImportService;
use App\Services\Calidad\MtGi11Service;
use App\Support\ExportRowLimit;
use App\Traits\HasMtGi11Tabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MtGi11Controller extends Controller
{
    use HasMtGi11Tabs;

    public function __construct(
        private readonly MtGi11AccessService $access,
        private readonly MtGi11DatatableService $datatable,
        private readonly MtGi11DashboardService $dashboard,
        private readonly MtGi11Service $solicitudes,
        private readonly MtGi11CatalogService $catalogs,
        private readonly MtGi11AdjuntoService $adjuntos,
        private readonly MtGi11ImportService $importService,
        private readonly MtGi11AuditLogService $audit,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canViewBoard($user), 403);

        $tabs = $this->access->visibleTabsFor($user);
        $firstTab = $tabs[0] ?? null;
        abort_unless($firstTab !== null, 403);

        $route = match ($firstTab) {
            'matriz' => 'calidad.mt-gi-11.matriz',
            'catalogos' => 'calidad.mt-gi-11.catalogos',
            default => 'calidad.mt-gi-11.dashboard',
        };

        return redirect()->route($route, $request->query());
    }

    public function dashboard(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canView($user), 403);

        $filters = $this->datatable->filtersFromRequest($request);
        $viewAll = $this->access->canViewAll($user);
        $metrics = $this->dashboard->metrics($filters, (int) $user->id, $viewAll);

        return view('areas.calidad.mt_gi_11.dashboard', [
            'subTabs' => $this->getMtGi11SubTabs('dashboard'),
            'filters' => $filters,
            'metrics' => $metrics,
            'options' => $this->filterOptions(),
            'kpiLinks' => $this->kpiLinks($filters),
        ]);
    }

    public function matriz(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canView($user), 403);

        $filters = $this->datatable->filtersFromRequest($request);
        $activeQuery = $this->datatable->activeQuery($filters);
        $canEdit = $this->access->canEdit($user);

        return view('areas.calidad.mt_gi_11.matriz', [
            'subTabs' => $this->getMtGi11SubTabs('matriz'),
            'filters' => $filters,
            'canEdit' => $canEdit,
            'viewAll' => $this->access->canViewAll($user),
            'options' => $this->filterOptions(),
            'estados' => $this->estadosWithColor(),
            'datatableUrl' => route('calidad.mt-gi-11.matriz.datatable', $activeQuery),
            'exportUrl' => route('calidad.mt-gi-11.matriz.export', $activeQuery),
            'importTemplateUrl' => route('calidad.mt-gi-11.matriz.plantilla'),
            'importUrl' => route('calidad.mt-gi-11.matriz.import'),
            'formOptions' => $this->formOptions(),
            'defaults' => $this->defaultFormValues(),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canView($user), 403);

        return $this->datatable->respond(
            $request,
            $this->datatable->filtersFromRequest($request),
            (int) $user->id,
            $this->access->canViewAll($user),
            $this->access->canEdit($user),
        );
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canView($user), 403);

        $filters = $this->datatable->filtersFromRequest($request);
        $query = $this->datatable->filteredQuery($filters, (int) $user->id, $this->access->canViewAll($user), ordered: false)
            ->with(['proceso', 'tipoDocumento', 'tipoSolicitud', 'cargoResponsable', 'estado', 'creator']);
        $maxRows = max(1, (int) config('mt_gi_11.export.max_rows', 10000));
        $rowCount = ExportRowLimit::count($query);

        if ($rowCount > $maxRows) {
            return redirect()
                ->route('calidad.mt-gi-11.matriz', $this->datatable->activeQuery($filters))
                ->with('error', ExportRowLimit::rejectionMessage($maxRows, $rowCount));
        }

        $this->audit->logEvent(
            action: 'export',
            eventType: 'export',
            metadata: ['rows' => $rowCount, 'filters' => $this->datatable->activeQuery($filters)],
            userId: (int) $user->id,
        );

        return MtGi11Export::downloadFilteredQuery($query);
    }

    public function importTemplate(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canEdit($user), 403);

        return (new MtGi11ImportTemplateExport)->download();
    }

    public function import(ImportMtGi11Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        try {
            $stats = $this->importService->import($path, (int) $user->id);
        } catch (\Throwable $exception) {
            return back()->withErrors(['import_file' => $exception->getMessage()]);
        }

        $this->audit->logEvent(
            action: 'import',
            eventType: 'import',
            metadata: ['imported' => $stats['imported'], 'skipped' => $stats['skipped']],
            userId: (int) $user->id,
        );

        return redirect()
            ->route('calidad.mt-gi-11.matriz')
            ->with('status', "Importación finalizada: {$stats['imported']} registro(s) creados, {$stats['skipped']} con error.")
            ->with('import_result', $stats);
    }

    public function create(): View
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canEdit($user), 403);

        return view('areas.calidad.mt_gi_11.form', [
            'subTabs' => $this->getMtGi11SubTabs('matriz'),
            'solicitud' => null,
            'canEdit' => true,
            'viewAll' => $this->access->canViewAll($user),
            'formOptions' => $this->formOptions(),
            'defaults' => $this->defaultFormValues(),
            'action' => route('calidad.mt-gi-11.matriz.store'),
        ]);
    }

    public function store(StoreMtGi11SolicitudRequest $request): RedirectResponse
    {
        $solicitud = $this->solicitudes->create(
            $request->validated(),
            $this->uploadedFiles($request),
            (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.mt-gi-11.matriz.show', $solicitud)
            ->with('status', 'Solicitud '.$solicitud->consecutivo.' creada.');
    }

    public function nuevaVersion(MtGi11Solicitud $solicitud): View
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canEdit($user), 403);

        $canManage = $this->access->canManageSolicitud($user, (int) $solicitud->created_by);
        abort_unless($canManage, 403);

        $solicitud->load($this->solicitudes->relations());

        return view('areas.calidad.mt_gi_11.form', [
            'subTabs' => $this->getMtGi11SubTabs('matriz'),
            'solicitud' => null,
            'canEdit' => true,
            'viewAll' => $this->access->canViewAll($user),
            'formOptions' => $this->formOptions(),
            'defaults' => $this->solicitudes->nuevaVersionDefaults($solicitud),
            'action' => route('calidad.mt-gi-11.matriz.store'),
            'sourceSolicitud' => $solicitud,
        ]);
    }

    public function show(MtGi11Solicitud $solicitud): View
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canView($user), 403);

        // Sin view_all solo se consultan las solicitudes propias.
        abort_unless($this->access->canViewAll($user) || $solicitud->isOwnedBy((int) $user->id), 403);

        $solicitud->load($this->solicitudes->relations());

        return view('areas.calidad.mt_gi_11.form', [
            'subTabs' => $this->getMtGi11SubTabs('matriz'),
            'solicitud' => $solicitud,
            'canEdit' => $this->access->canManageSolicitud($user, (int) $solicitud->created_by),
            'viewAll' => $this->access->canViewAll($user),
            'formOptions' => $this->formOptions($solicitud),
            'defaults' => [],
            'action' => route('calidad.mt-gi-11.matriz.update', $solicitud),
        ]);
    }

    public function update(UpdateMtGi11SolicitudRequest $request, MtGi11Solicitud $solicitud): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless($this->access->canManageSolicitud($user, (int) $solicitud->created_by), 403);

        $this->solicitudes->update(
            $solicitud,
            $request->validated(),
            $this->uploadedFiles($request),
            (int) $user->id,
        );

        return redirect()
            ->route('calidad.mt-gi-11.matriz.show', $solicitud)
            ->with('status', 'Solicitud '.$solicitud->consecutivo.' actualizada.');
    }

    public function destroy(MtGi11Solicitud $solicitud): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless($this->access->canManageSolicitud($user, (int) $solicitud->created_by), 403);

        $consecutivo = (string) $solicitud->consecutivo;
        $this->solicitudes->delete($solicitud, (int) $user->id);

        return redirect()
            ->route('calidad.mt-gi-11.matriz')
            ->with('status', 'Solicitud '.$consecutivo.' eliminada.');
    }

    public function storeAdjuntos(StoreMtGi11AdjuntosRequest $request, MtGi11Solicitud $solicitud): RedirectResponse
    {
        abort_unless($request->user() !== null, 403);

        $stored = $this->adjuntos->storeMany($solicitud, $this->uploadedFiles($request), (int) $request->user()->id);

        $this->audit->logEvent(
            action: 'update',
            eventType: 'mt_gi_11_adjunto',
            metadata: [
                'solicitud_id' => (int) $solicitud->id,
                'consecutivo' => (string) $solicitud->consecutivo,
                'files' => array_map(fn (MtGi11Adjunto $adjunto): string => $adjunto->original_name, $stored),
            ],
            userId: (int) $request->user()->id,
        );

        return redirect()
            ->route('calidad.mt-gi-11.matriz.show', $solicitud)
            ->with('status', 'Archivos adjuntados.')
            ->withFragment('adjuntos');
    }

    public function previewAdjunto(MtGi11Solicitud $solicitud, MtGi11Adjunto $adjunto): StreamedResponse
    {
        $this->authorizeAdjunto($solicitud, $adjunto);
        abort_unless($adjunto->isPreviewable(), 404);

        $disk = (string) config('mt_gi_11.adjuntos.disk', 'local');
        abort_unless(Storage::disk($disk)->exists($adjunto->stored_path), 404);

        // inline: el navegador muestra PDF/imagen sin descargar.
        return Storage::disk($disk)->response($adjunto->stored_path, $adjunto->original_name, [
            'Content-Disposition' => 'inline; filename="'.$adjunto->original_name.'"',
        ]);
    }

    public function downloadAdjunto(MtGi11Solicitud $solicitud, MtGi11Adjunto $adjunto): StreamedResponse
    {
        $this->authorizeAdjunto($solicitud, $adjunto);

        $disk = (string) config('mt_gi_11.adjuntos.disk', 'local');
        abort_unless(Storage::disk($disk)->exists($adjunto->stored_path), 404);

        return Storage::disk($disk)->download($adjunto->stored_path, $adjunto->original_name);
    }

    public function destroyAdjunto(MtGi11Solicitud $solicitud, MtGi11Adjunto $adjunto): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless($this->access->canManageSolicitud($user, (int) $solicitud->created_by), 403);
        abort_unless((int) $adjunto->solicitud_id === (int) $solicitud->id, 404);

        $metadata = [
            'solicitud_id' => (int) $solicitud->id,
            'consecutivo' => (string) $solicitud->consecutivo,
            'adjunto_id' => (int) $adjunto->id,
            'original_name' => (string) $adjunto->original_name,
        ];

        $this->adjuntos->delete($adjunto);

        $this->audit->logEvent(
            action: 'delete',
            eventType: 'mt_gi_11_adjunto',
            metadata: $metadata,
            userId: (int) $user->id,
        );

        return redirect()
            ->route('calidad.mt-gi-11.matriz.show', $solicitud)
            ->with('status', 'Adjunto eliminado.')
            ->withFragment('adjuntos');
    }

    public function catalogos(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canEditParameters($user), 403);

        return view('areas.calidad.mt_gi_11.catalogos', [
            'subTabs' => $this->getMtGi11SubTabs('catalogos'),
            'catalogs' => $this->catalogs->catalogsForAdmin(),
            'labels' => collect($this->catalogs->managedTypes())
                ->mapWithKeys(fn ($class, string $type): array => [$type => $this->catalogs->labelFor($type)])
                ->all(),
            'activeCatalog' => $request->string('catalog')->toString(),
        ]);
    }

    public function storeCatalog(StoreMtGi11CatalogItemRequest $request, string $type): RedirectResponse
    {
        abort_unless($this->catalogs->isManagedType($type), 404);

        $class = $this->catalogs->modelClassFor($type);
        $name = $request->string('name')->toString();

        $payload = [
            'code' => $this->catalogs->uniqueCode($type, $name),
            'name' => $name,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];

        // El color solo existe en el catálogo de estados.
        if ($type === 'estados') {
            $payload['color'] = $request->string('color')->toString() ?: 'muted';
        }

        $class::query()->create($payload);

        return redirect()
            ->route('calidad.mt-gi-11.catalogos', ['catalog' => $type])
            ->with('status', 'Catálogo actualizado.');
    }

    public function updateCatalog(UpdateMtGi11CatalogItemRequest $request, string $type, int $item): RedirectResponse
    {
        abort_unless($this->catalogs->isManagedType($type), 404);

        $model = $this->catalogs->findItemOrFail($type, $item);

        $payload = [
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', $model->sort_order ?? 0),
        ];

        if ($type === 'estados') {
            $payload['color'] = $request->string('color')->toString() ?: 'muted';
        }

        $model->update($payload);

        return redirect()
            ->route('calidad.mt-gi-11.catalogos', ['catalog' => $type])
            ->with('status', 'Catálogo actualizado.');
    }

    public function destroyCatalog(string $type, int $item): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canEditParameters($user), 403);
        abort_unless($this->catalogs->isManagedType($type), 404);

        $model = $this->catalogs->findItemOrFail($type, $item);

        try {
            $this->catalogs->assertDeletable($type, $model);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('calidad.mt-gi-11.catalogos', ['catalog' => $type])
                ->withErrors($exception->errors());
        }

        $model->delete();

        return redirect()
            ->route('calidad.mt-gi-11.catalogos', ['catalog' => $type])
            ->with('status', 'Valor eliminado del catálogo.');
    }

    /**
     * Autoriza el acceso a un adjunto verificando pertenencia y alcance.
     */
    private function authorizeAdjunto(MtGi11Solicitud $solicitud, MtGi11Adjunto $adjunto): void
    {
        $user = auth()->user();
        abort_unless($user !== null && $this->access->canView($user), 403);
        abort_unless((int) $adjunto->solicitud_id === (int) $solicitud->id, 404);
        abort_unless($this->access->canViewAll($user) || $solicitud->isOwnedBy((int) $user->id), 403);
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(Request $request): array
    {
        return array_values(array_filter(
            (array) $request->file('attachments', []),
            fn ($file): bool => $file instanceof UploadedFile,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'procesos' => $this->catalogs->optionsFor('procesos'),
            'tipos_documento' => $this->catalogs->optionsFor('tipos_documento'),
            'tipos_solicitud' => $this->catalogs->optionsFor('tipos_solicitud'),
            'cargos_responsable' => $this->catalogs->optionsFor('cargos_responsable'),
            'estados' => $this->catalogs->optionsFor('estados'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?MtGi11Solicitud $solicitud = null): array
    {
        return [
            'procesos' => $this->catalogs->optionsFor('procesos', $solicitud?->proceso_id),
            'tipos_documento' => $this->catalogs->optionsFor('tipos_documento', $solicitud?->tipo_documento_id),
            'tipos_solicitud' => $this->catalogs->optionsFor('tipos_solicitud', $solicitud?->tipo_solicitud_id),
            'cargos_responsable' => $this->catalogs->optionsFor('cargos_responsable', $solicitud?->cargo_responsable_id),
            'estados' => $this->catalogs->optionsFor('estados', $solicitud?->estado_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormValues(): array
    {
        $pendiente = MtGi11Estado::query()->where('code', MtGi11Estado::CODE_PENDIENTE)->value('id');

        return [
            'fecha_solicitud' => now()->toDateString(),
            'proceso_id' => '',
            'consecutivo' => '',
            'tipo_documento_id' => '',
            'nombre_documento' => '',
            'tipo_solicitud_id' => '',
            'codigo_actual' => '',
            'version_actual' => '',
            'justificacion' => '',
            'responsable_proceso' => '',
            'cargo_responsable_id' => '',
            'codigo_estandarizacion' => '',
            'fecha_estandarizacion' => '',
            'version_actualizada' => '',
            'estado_id' => (string) ($pendiente ?? ''),
        ];
    }

    /**
     * Estados con su color para los botones-filtro del listado.
     *
     * @return list<array{id: string, label: string, color: string}>
     */
    private function estadosWithColor(): array
    {
        return MtGi11Estado::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'color'])
            ->map(fn (MtGi11Estado $estado): array => [
                'id' => (string) $estado->id,
                'label' => (string) $estado->name,
                'color' => $estado->pillClass(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    private function kpiLinks(array $filters): array
    {
        $shared = $this->datatable->activeQuery($filters);

        return [
            'total' => route('calidad.mt-gi-11.matriz', $shared),
            'estados' => collect($this->estadosWithColor())
                ->mapWithKeys(fn (array $estado): array => [
                    $estado['id'] => route('calidad.mt-gi-11.matriz', array_merge($shared, ['estado_id' => $estado['id']])),
                ])
                ->all(),
        ];
    }
}

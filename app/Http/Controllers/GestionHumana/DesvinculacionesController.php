<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\LookupBulkTerminationRequest;
use App\Http\Requests\GestionHumana\ProcessBulkTerminationRequest;
use App\Http\Requests\GestionHumana\RevertTerminationFollowupRequest;
use App\Http\Requests\GestionHumana\UpdateTerminationFollowupRequest;
use App\Models\EmployeeTerminationFollowup;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Services\Access\DesvinculacionesAccessService;
use App\Services\GestionHumana\BulkTerminationService;
use App\Services\GestionHumana\DesvinculacionesAuditLogService;
use App\Services\GestionHumana\EmployeeTerminationFollowupService;
use App\Services\GestionHumana\TerminationFollowupDatatableService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesvinculacionesController extends Controller
{
    private const ZIP_CACHE_PREFIX = 'desvinculaciones.bulk_zip.';

    private const ZIP_CACHE_TTL_SECONDS = 900;

    public function __construct(
        private readonly DesvinculacionesAccessService $desvinculacionesAccess,
        private readonly BulkTerminationService $bulkTerminationService,
        private readonly EmployeeTerminationFollowupService $followupService,
        private readonly TerminationFollowupDatatableService $followupDatatableService,
        private readonly DesvinculacionesAuditLogService $auditLogService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $this->authorizeView();

        return redirect()->route('gestion-humana.desvinculaciones.masivos', $request->query());
    }

    public function masivos(): View
    {
        $this->authorizeView();

        $canMasivos = $this->desvinculacionesAccess->canMasivos(auth()->user());

        return view('areas.gestion_humana.desvinculaciones.masivos', [
            'subTabs' => $this->subTabs('masivos'),
            'canMasivos' => $canMasivos,
            'templateOptions' => $canMasivos ? $this->templateOptions() : [],
            'signatoryOptions' => $canMasivos ? $this->signatoryOptions() : [],
            'causeOptions' => $canMasivos ? $this->causeOptions() : [],
            'rehireOptions' => [
                ['value' => '1', 'label' => 'Si'],
                ['value' => '0', 'label' => 'No'],
            ],
        ]);
    }

    public function seguimientos(): View
    {
        $this->authorizeView();

        $canEdit = $this->desvinculacionesAccess->canEditSeguimientos(auth()->user());

        return view('areas.gestion_humana.desvinculaciones.seguimientos', [
            'subTabs' => $this->subTabs('seguimientos'),
            'canEditSeguimientos' => $canEdit,
            'checkFields' => EmployeeTerminationFollowup::CHECK_FIELDS,
            'checkLabels' => EmployeeTerminationFollowup::CHECK_LABELS,
            'datatableUrl' => route('gestion-humana.desvinculaciones.seguimientos.datatable'),
            'exportUrl' => route('gestion-humana.desvinculaciones.seguimientos.export'),
            'filters' => [
                'q' => request()->string('q')->toString(),
                'status' => request()->string('status')->toString() ?: 'todos',
                'fecha_desde' => request()->string('fecha_desde')->toString(),
                'fecha_hasta' => request()->string('fecha_hasta')->toString(),
            ],
        ]);
    }

    public function seguimientosDatatable(Request $request): JsonResponse
    {
        $this->authorizeView();

        return $this->followupDatatableService->respond($request);
    }

    public function exportSeguimientos(Request $request): StreamedResponse
    {
        $this->authorizeView();

        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:todos,incompletos,ok_todo,sin_carta'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $rows = $this->followupDatatableService->filteredQuery($request)->get();

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'seguimientos_excel',
            metadata: [
                'row_count' => $rows->count(),
                'q' => trim($request->string('q')->toString()),
                'status' => $request->string('status')->toString() ?: 'todos',
                'fecha_desde' => $request->date('fecha_desde')?->toDateString(),
                'fecha_hasta' => $request->date('fecha_hasta')?->toDateString(),
            ],
        );

        return (new BaseExport(
            $rows,
            $this->followupDatatableService->exportColumns(),
            'seguimientos_desvinculaciones_'.now()->format('Y-m-d').'.xlsx',
            'Seguimientos desvinculaciones - '.config('app.name'),
        ))->download();
    }

    public function updateSeguimiento(
        UpdateTerminationFollowupRequest $request,
        EmployeeTerminationFollowup $followup,
    ): JsonResponse {
        $updated = $this->followupService->updatePartial(
            $followup,
            $request->editablePayload(),
            $request->user(),
        );

        return response()->json([
            'ok' => true,
            'followup' => $this->followupDatatableService->formatRow($updated),
        ]);
    }

    public function revertSeguimiento(
        RevertTerminationFollowupRequest $request,
        EmployeeTerminationFollowup $followup,
    ): JsonResponse {
        $this->followupService->revert(
            $followup,
            $request->user(),
            $request->reason(),
        );

        return response()->json([
            'ok' => true,
            'message' => 'Desvinculacion revertida. El empleado quedo activo nuevamente.',
        ]);
    }

    public function templates(): JsonResponse
    {
        $this->authorizeMasivos();

        return response()->json([
            'templates' => collect($this->templateOptions())
                ->map(static fn (array $opt): array => [
                    'id' => (int) $opt['value'],
                    'label' => $opt['label'],
                ])
                ->values()
                ->all(),
        ]);
    }

    public function signatories(): JsonResponse
    {
        $this->authorizeMasivos();

        return response()->json([
            'firmas' => PayrollCatalogItem::query()
                ->ofType('firmas')
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function lookup(LookupBulkTerminationRequest $request): JsonResponse
    {
        $result = $this->bulkTerminationService->lookupActiveByDocument(
            (string) $request->validated('document_number'),
        );

        if (! $result['ok']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Contrato HTTP ZIP: JSON con ok/failed/summary + download_token opcional.
     * El cliente descarga el ZIP con GET masivos.download (Content-Disposition).
     */
    public function process(ProcessBulkTerminationRequest $request): JsonResponse
    {
        $result = $this->bulkTerminationService->process(
            $request->rows(),
            $request->user(),
        );

        $downloadToken = null;
        $downloadUrl = null;

        if ($result['zip_absolute_path'] !== null && is_file($result['zip_absolute_path'])) {
            $downloadToken = Str::uuid()->toString();
            Cache::put(
                self::ZIP_CACHE_PREFIX.$downloadToken,
                [
                    'path' => $result['zip_absolute_path'],
                    'name' => $result['zip_download_name'],
                    'user_id' => (int) $request->user()->id,
                ],
                self::ZIP_CACHE_TTL_SECONDS,
            );
            $downloadUrl = route('gestion-humana.desvinculaciones.masivos.download', [
                'token' => $downloadToken,
            ]);
        }

        return response()->json([
            'ok' => $result['ok'],
            'failed' => $result['failed'],
            'summary' => $result['summary'],
            'download_token' => $downloadToken,
            'download_url' => $downloadUrl,
        ]);
    }

    public function downloadZip(string $token): BinaryFileResponse
    {
        $this->authorizeMasivos();

        $payload = Cache::pull(self::ZIP_CACHE_PREFIX.$token);

        abort_unless(is_array($payload), 404);
        abort_unless((int) ($payload['user_id'] ?? 0) === (int) auth()->id(), 403);

        $path = (string) ($payload['path'] ?? '');
        $name = (string) ($payload['name'] ?? 'desvinculaciones.zip');

        abort_unless($path !== '' && is_file($path), 404);

        return response()->download($path, $name)->deleteFileAfterSend(true);
    }

    private function authorizeView(): void
    {
        abort_unless($this->desvinculacionesAccess->canView(auth()->user()), 403);
    }

    private function authorizeMasivos(): void
    {
        abort_unless($this->desvinculacionesAccess->canMasivos(auth()->user()), 403);
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    private function subTabs(string $activeTab): array
    {
        $tabs = config('access.desvinculaciones_tabs', []);

        return collect($tabs)
            ->map(fn (string $label, string $key): array => [
                'label' => $label,
                'url' => match ($key) {
                    'seguimientos' => route('gestion-humana.desvinculaciones.seguimientos'),
                    default => route('gestion-humana.desvinculaciones.masivos'),
                },
                'active' => $key === $activeTab,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function templateOptions(): array
    {
        $typeCode = (string) config('employee_ficha.word_document_type_codes.desvinculacion');

        return TerminationLetterDocumentTemplate::query()
            ->forTypeCode($typeCode)
            ->withFile()
            ->ordered()
            ->get()
            ->filter(static function (TerminationLetterDocumentTemplate $template): bool {
                return Storage::disk('local')->exists((string) $template->template_path);
            })
            ->values()
            ->map(static fn (TerminationLetterDocumentTemplate $template): array => [
                'value' => (string) $template->id,
                'label' => $template->label,
            ])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function signatoryOptions(): array
    {
        return PayrollCatalogItem::query()
            ->ofType('firmas')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(static fn (PayrollCatalogItem $item): array => [
                'value' => (string) $item->id,
                'label' => $item->name.' — '.$item->code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function causeOptions(): array
    {
        return PayrollCatalogItem::query()
            ->ofType('termination_cause')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(static fn (PayrollCatalogItem $item): array => [
                'value' => (string) $item->code,
                'label' => $item->code.' — '.$item->name,
            ])
            ->values()
            ->all();
    }
}

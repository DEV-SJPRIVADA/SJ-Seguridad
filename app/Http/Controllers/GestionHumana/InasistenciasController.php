<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\InasistenciasHistoricoExport;
use App\Exports\InasistenciasImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\Inasistencias\GenerateInasistenciasRequest;
use App\Http\Requests\GestionHumana\Inasistencias\ImportPreviewInasistenciasRequest;
use App\Http\Requests\GestionHumana\Inasistencias\LookupInasistenciasRequest;
use App\Models\InasistenciaCarta;
use App\Models\PayrollCatalogItem;
use App\Services\Access\InasistenciasAccessService;
use App\Services\GestionHumana\InasistenciasAuditLogService;
use App\Services\GestionHumana\InasistenciasGeneratorService;
use App\Traits\HasInasistenciasTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InasistenciasController extends Controller
{
    use HasInasistenciasTabs;

    public function __construct(
        private readonly InasistenciasAccessService $inasistenciasAccess,
        private readonly InasistenciasGeneratorService $generator,
        private readonly InasistenciasImportTemplateExport $importTemplateExport,
        private readonly InasistenciasHistoricoExport $historicoExport,
        private readonly InasistenciasAuditLogService $auditLogService,
    ) {}

    /**
     * Pestaña Generar: grilla en memoria + Word.
     */
    public function generar(): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $this->inasistenciasAccess->canEdit($user)) {
            if ($this->inasistenciasAccess->canView($user)) {
                return redirect()->route('gestion-humana.inasistencias.historico');
            }

            throw new HttpException(
                403,
                'Necesita el permiso Inasistencias: Generar para usar esta pestaña.'
            );
        }

        return view('areas.gestion_humana.inasistencias.generar', [
            'maxRows' => (int) config('inasistencias.max_rows', 500),
            'signatoryOptions' => $this->signatoryOptions(),
            'lookupUrl' => route('gestion-humana.inasistencias.lookup'),
            'generateUrl' => route('gestion-humana.inasistencias.generate'),
            'importTemplateUrl' => route('gestion-humana.inasistencias.import-template'),
            'importPreviewUrl' => route('gestion-humana.inasistencias.import-preview'),
            'subTabs' => $this->getInasistenciasSubTabs('generar'),
        ]);
    }

    /**
     * Pestaña Histórico: registros generados (sin archivo Word).
     */
    public function historico(Request $request): View
    {
        $user = auth()->user();

        if (! $this->inasistenciasAccess->canView($user)) {
            throw new HttpException(
                403,
                'Necesita el permiso Inasistencias: Ver para consultar el histórico.'
            );
        }

        $query = InasistenciaCarta::query()
            ->with(['generator:id,name'])
            ->orderByDesc('generated_at')
            ->orderByDesc('id');

        $cedula = trim((string) $request->string('q')->toString());
        if ($cedula !== '') {
            $query->where('document_number', 'like', '%'.$cedula.'%');
        }

        $firma = trim((string) $request->string('firma')->toString());
        if ($firma !== '') {
            $query->where(function ($builder) use ($firma): void {
                $builder->where('firma_name', 'like', '%'.$firma.'%')
                    ->orWhere('cargo_firma', 'like', '%'.$firma.'%');
            });
        }

        $desde = $request->input('fecha_desde');
        if (is_string($desde) && $desde !== '') {
            $query->whereDate('generated_at', '>=', $desde);
        }

        $hasta = $request->input('fecha_hasta');
        if (is_string($hasta) && $hasta !== '') {
            $query->whereDate('generated_at', '<=', $hasta);
        }

        $records = $query->paginate(50)->withQueryString();

        return view('areas.gestion_humana.inasistencias.historico', [
            'records' => $records,
            'filters' => [
                'q' => $cedula,
                'firma' => $firma,
                'fecha_desde' => is_string($desde) ? $desde : '',
                'fecha_hasta' => is_string($hasta) ? $hasta : '',
            ],
            'canEdit' => $this->inasistenciasAccess->canEdit($user),
            'exportUrl' => route('gestion-humana.inasistencias.historico.export', $request->query()),
            'subTabs' => $this->getInasistenciasSubTabs('historico'),
        ]);
    }

    public function exportHistorico(Request $request): StreamedResponse
    {
        abort_unless($this->inasistenciasAccess->canView(auth()->user()), 403);

        $query = InasistenciaCarta::query()
            ->with(['generator:id,name'])
            ->orderByDesc('generated_at')
            ->orderByDesc('id');

        $cedula = trim((string) $request->string('q')->toString());
        if ($cedula !== '') {
            $query->where('document_number', 'like', '%'.$cedula.'%');
        }

        $firma = trim((string) $request->string('firma')->toString());
        if ($firma !== '') {
            $query->where(function ($builder) use ($firma): void {
                $builder->where('firma_name', 'like', '%'.$firma.'%')
                    ->orWhere('cargo_firma', 'like', '%'.$firma.'%');
            });
        }

        $desde = $request->input('fecha_desde');
        if (is_string($desde) && $desde !== '') {
            $query->whereDate('generated_at', '>=', $desde);
        }

        $hasta = $request->input('fecha_hasta');
        if (is_string($hasta) && $hasta !== '') {
            $query->whereDate('generated_at', '<=', $hasta);
        }

        $rows = $query->limit(5000)->get()->map(static function (InasistenciaCarta $row): array {
            return [
                'document_number' => $row->document_number,
                'full_name' => $row->full_name,
                'cargo' => $row->cargo,
                'ciudad' => $row->ciudad,
                'fecha_ini_aus' => optional($row->fecha_ini_aus)?->format('Y-m-d'),
                'dias' => $row->dias,
                'firma_name' => $row->firma_name,
                'cargo_firma' => $row->cargo_firma,
                'generated_at' => optional($row->generated_at)?->format('Y-m-d H:i'),
                'generated_by_name' => $row->generator?->name,
            ];
        });

        return $this->historicoExport->download($rows);
    }

    public function lookup(LookupInasistenciasRequest $request): JsonResponse
    {
        $results = $this->generator->lookup($request->documentNumbers());

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    public function generate(GenerateInasistenciasRequest $request): BinaryFileResponse
    {
        $result = $this->generator->generate($request->rows(), (int) auth()->id());

        $this->auditLogService->logEvent(
            eventType: 'inasistencias_generate',
            action: 'generate',
            metadata: [
                'row_count' => $result['row_count'],
                'output_type' => $result['output_type'],
                'template_id' => $result['template_id'],
            ],
            userId: (int) auth()->id(),
        );

        return response()
            ->download($result['absolute_path'], $result['download_name'])
            ->deleteFileAfterSend(true);
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless($this->inasistenciasAccess->canEdit(auth()->user()), 403);

        return $this->importTemplateExport->download();
    }

    public function importPreview(ImportPreviewInasistenciasRequest $request): JsonResponse
    {
        $preview = $this->generator->previewImport($request->file('file'));

        return response()->json([
            'ok' => true,
            'rows' => $preview['rows'],
            'summary' => $preview['summary'],
        ]);
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
}

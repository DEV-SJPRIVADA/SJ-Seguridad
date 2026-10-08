<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\CartasNotificacionImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\CartasNotificacion\GenerateCartasNotificacionRequest;
use App\Http\Requests\GestionHumana\CartasNotificacion\ImportPreviewCartasNotificacionRequest;
use App\Http\Requests\GestionHumana\CartasNotificacion\LookupCartasNotificacionRequest;
use App\Models\PayrollCatalogItem;
use App\Services\Access\CartasNotificacionAccessService;
use App\Services\GestionHumana\CartasNotificacionAuditLogService;
use App\Services\GestionHumana\CartasNotificacionGeneratorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CartasNotificacionController extends Controller
{
    public function __construct(
        private readonly CartasNotificacionAccessService $cartasNotificacionAccess,
        private readonly CartasNotificacionGeneratorService $generator,
        private readonly CartasNotificacionImportTemplateExport $importTemplateExport,
        private readonly CartasNotificacionAuditLogService $auditLogService,
    ) {}

    /**
     * Tablero Cartas Notificación: grilla en memoria + generación Word.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (! $this->cartasNotificacionAccess->canEdit($user)) {
            throw new HttpException(
                403,
                'Necesita el permiso Cartas Notificación: Generar para usar este tablero.'
            );
        }

        return view('areas.gestion_humana.cartas_notificacion.index', [
            'maxRows' => (int) config('cartas_notificacion.max_rows', 500),
            'signatoryOptions' => $this->signatoryOptions(),
            'duracionContratoOptions' => $this->duracionContratoOptions(),
            'lookupUrl' => route('gestion-humana.cartas-notificacion.lookup'),
            'generateUrl' => route('gestion-humana.cartas-notificacion.generate'),
            'importTemplateUrl' => route('gestion-humana.cartas-notificacion.import-template'),
            'importPreviewUrl' => route('gestion-humana.cartas-notificacion.import-preview'),
        ]);
    }

    public function lookup(LookupCartasNotificacionRequest $request): JsonResponse
    {
        $results = $this->generator->lookup($request->documentNumbers());

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    public function generate(GenerateCartasNotificacionRequest $request): BinaryFileResponse
    {
        $result = $this->generator->generate($request->rows());

        $this->auditLogService->logEvent(
            eventType: 'cartas_notificacion_generate',
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
        abort_unless($this->cartasNotificacionAccess->canEdit(auth()->user()), 403);

        return $this->importTemplateExport->download();
    }

    public function importPreview(ImportPreviewCartasNotificacionRequest $request): JsonResponse
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

    /**
     * @return list<array{value: string, label: string}>
     */
    private function duracionContratoOptions(): array
    {
        $options = config('cartas_notificacion.duracion_contrato_options', [6, 12]);

        return collect($options)
            ->map(static fn (mixed $months): array => [
                'value' => (string) (int) $months,
                'label' => (string) (int) $months,
            ])
            ->values()
            ->all();
    }
}

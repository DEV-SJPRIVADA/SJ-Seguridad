<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\RequestPurchaseQuotationApprovalRequest;
use App\Http\Requests\PurchaseRequests\ResolvePurchaseQuotationApprovalRequest;
use App\Http\Requests\PurchaseRequests\StorePurchaseQuotationRequest;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequests\PurchaseQuotationBatchService;
use App\Services\PurchaseRequests\PurchaseQuotationFileService;
use App\Services\PurchaseRequests\PurchaseQuotationService;
use App\Support\PurchaseQuotationBatch;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Cotizaciones de la bandeja de Compras, agrupadas por lote.
 * Cada lote representa una OC futura y se envía completo a un director, que elige
 * una cotización ganadora en la plataforma o por el enlace firmado del correo.
 */
class PurchaseQuotationController extends Controller
{
    use HasPurchaseTabs;

    public function store(
        StorePurchaseQuotationRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseQuotationService $service,
    ): RedirectResponse {
        Gate::authorize('process', $purchaseRequest);

        $files = $request->file('files') ?? [];

        try {
            $service->storeBatch(
                $purchaseRequest,
                $request->user(),
                $request->validated('lote'),
                $request->validated('lote_label'),
                $files,
                $request->validated('descriptions') ?? [],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()])->withInput();
        }

        return $this->backToPurchase($module, $purchaseRequest)
            ->with('status', 'Grupo de cotizaciones actualizado con '.count($files).' archivo(s).');
    }

    public function destroy(
        Request $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseQuotation $purchaseQuotation,
        PurchaseQuotationService $service,
    ): RedirectResponse {
        Gate::authorize('process', $purchaseRequest);
        $this->assertBelongsToRequest($purchaseRequest, $purchaseQuotation);

        try {
            $service->delete($purchaseQuotation, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        return $this->backToPurchase($module, $purchaseRequest)
            ->with('status', 'Cotización eliminada.');
    }

    public function requestApproval(
        RequestPurchaseQuotationApprovalRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        string $lote,
        PurchaseQuotationService $service,
    ): RedirectResponse {
        Gate::authorize('process', $purchaseRequest);

        try {
            $service->request($purchaseRequest, $lote, $request->user(), (int) $request->validated('aprobador_id'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['aprobador_id' => $exception->getMessage()])->withInput();
        }

        return $this->backToPurchase($module, $purchaseRequest)
            ->with('status', 'Aprobación del grupo de cotizaciones solicitada al director.');
    }

    public function withdraw(
        Request $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        string $lote,
        PurchaseQuotationService $service,
    ): RedirectResponse {
        Gate::authorize('process', $purchaseRequest);

        try {
            $service->withdraw($purchaseRequest, $lote, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['aprobador_id' => $exception->getMessage()]);
        }

        return $this->backToPurchase($module, $purchaseRequest)
            ->with('status', 'Solicitud de aprobación del grupo retirada.');
    }

    /**
     * Archivo para Compras (bandeja). Se muestra inline para PDF o imagen.
     */
    public function file(
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseQuotation $purchaseQuotation,
    ): BinaryFileResponse {
        Gate::authorize('process', $purchaseRequest);
        $this->assertBelongsToRequest($purchaseRequest, $purchaseQuotation);

        return $this->fileResponse($purchaseQuotation);
    }

    /**
     * Revisión del director en la plataforma (Autorizaciones compras → Cotizaciones).
     */
    public function show(
        Request $request,
        string $module,
        string $lote,
        PurchaseQuotationBatchService $batches,
    ): View {
        $quotations = $this->batchQuotationsOrFail($lote);

        abort_unless(
            $quotations->every(fn (PurchaseQuotation $quotation): bool => (int) $quotation->aprobador_id === (int) $request->user()->id),
            403,
        );

        $batch = $this->batchOrFail($batches, $quotations);

        return view('modules.purchase-requests.approval.quotation-approval', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'batch' => $batch,
            'fileUrls' => $this->approvalFileUrls($module, $batch),
            'decideUrl' => route('purchase-requests.approval.quotation.update', [
                'module' => $module,
                'lote' => $batch->key,
            ]),
            'backUrl' => route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'cotizaciones']),
        ]);
    }

    public function update(
        ResolvePurchaseQuotationApprovalRequest $request,
        string $module,
        string $lote,
    ): RedirectResponse {
        $approve = $request->validated('decision') === 'aprobar';
        $purchaseRequest = $this->batchQuotationsOrFail($lote)->first()?->purchaseRequest;

        if ($purchaseRequest === null) {
            abort(404);
        }

        try {
            app(PurchaseQuotationService::class)->resolve(
                $purchaseRequest,
                $lote,
                (int) $request->user()->id,
                $approve,
                $request->validated('purchase_quotation_id') !== null
                    ? (int) $request->validated('purchase_quotation_id')
                    : null,
                $request->validated('comentario'),
                'web',
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['comentario' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'cotizaciones'])
            ->with('status', 'Grupo de cotizaciones '.($approve ? 'aprobado' : 'rechazado').'.');
    }

    public function approvalFile(
        Request $request,
        string $module,
        PurchaseQuotation $purchaseQuotation,
    ): BinaryFileResponse {
        abort_unless((int) $purchaseQuotation->aprobador_id === (int) $request->user()->id, 403);

        return $this->fileResponse($purchaseQuotation);
    }

    /**
     * Enlace firmado del correo (sin login).
     */
    public function emailShow(
        Request $request,
        string $lote,
        PurchaseQuotationService $service,
        PurchaseQuotationBatchService $batches,
    ): View {
        $this->forceUrlsFromRequest($request);
        $quotations = $this->batchQuotationsOrFail($lote);
        $this->assertEmailApprover($request, $quotations);

        $batch = $this->batchOrFail($batches, $quotations);
        $purchaseRequest = $batch->purchaseRequest();
        $pending = $batch->estaPendiente();

        abort_if($purchaseRequest === null, 404);

        return view('modules.purchase-requests.quotation-approval-email', [
            'batch' => $batch,
            'fileUrls' => $this->emailFileUrls($batch, $service),
            'decideUrl' => $pending
                ? $service->emailUpdateUrl($purchaseRequest, $lote, (int) $request->query('director'))
                : null,
            'alreadyResolved' => ! $pending,
            'result' => null,
        ]);
    }

    public function emailUpdate(
        ResolvePurchaseQuotationApprovalRequest $request,
        string $lote,
        PurchaseQuotationService $service,
        PurchaseQuotationBatchService $batches,
    ): View|RedirectResponse {
        $this->forceUrlsFromRequest($request);
        $quotations = $this->batchQuotationsOrFail($lote);
        $approverId = $this->assertEmailApprover($request, $quotations);
        $approve = $request->validated('decision') === 'aprobar';
        $purchaseRequest = $quotations->first()?->purchaseRequest;

        if ($purchaseRequest === null) {
            abort(404);
        }

        if ($quotations->contains(fn (PurchaseQuotation $quotation): bool => $quotation->estaPendiente())) {
            try {
                $service->resolve(
                    $purchaseRequest,
                    $lote,
                    $approverId,
                    $approve,
                    $request->validated('purchase_quotation_id') !== null
                        ? (int) $request->validated('purchase_quotation_id')
                        : null,
                    $request->validated('comentario'),
                    'email',
                );
            } catch (InvalidArgumentException $exception) {
                return back()->withErrors(['comentario' => $exception->getMessage()])->withInput();
            }
        }

        $quotations = $this->batchQuotationsOrFail($lote);
        $batch = $this->batchOrFail($batches, $quotations);

        return view('modules.purchase-requests.quotation-approval-email', [
            'batch' => $batch,
            'fileUrls' => $this->emailFileUrls($batch, $service),
            'decideUrl' => null,
            'alreadyResolved' => true,
            'result' => $approve ? 'aprobada' : 'rechazada',
        ]);
    }

    /**
     * Archivo desde el enlace firmado del correo.
     */
    public function emailFile(Request $request, PurchaseQuotation $purchaseQuotation): BinaryFileResponse
    {
        $approverId = (int) $request->query('director', $request->input('director'));

        abort_unless($approverId > 0 && (int) $purchaseQuotation->aprobador_id === $approverId, 403);

        return $this->fileResponse($purchaseQuotation);
    }

    /**
     * @return Collection<int, PurchaseQuotation>
     */
    private function batchQuotationsOrFail(string $lote): Collection
    {
        $quotations = PurchaseQuotation::query()
            ->with(['purchaseRequest.user', 'aprobador', 'solicitadaPor', 'uploadedBy', 'purchaseOrder'])
            ->where('lote', $lote)
            ->orderBy('lote_orden')
            ->orderBy('id')
            ->get();

        abort_if($quotations->isEmpty(), 404);

        return $quotations;
    }

    /**
     * @param  Collection<int, PurchaseQuotation>  $quotations
     */
    private function batchOrFail(PurchaseQuotationBatchService $batches, Collection $quotations): PurchaseQuotationBatch
    {
        $batch = $batches->group($quotations)->first();

        abort_unless($batch instanceof PurchaseQuotationBatch, 404);

        return $batch;
    }

    /**
     * @return array<int, string>
     */
    private function approvalFileUrls(string $module, PurchaseQuotationBatch $batch): array
    {
        return $batch->quotations
            ->mapWithKeys(fn (PurchaseQuotation $quotation): array => [
                (int) $quotation->getKey() => route('purchase-requests.approval.quotation.file', [
                    'module' => $module,
                    'purchase_quotation' => $quotation->getKey(),
                ]),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function emailFileUrls(PurchaseQuotationBatch $batch, PurchaseQuotationService $service): array
    {
        return $batch->quotations
            ->mapWithKeys(fn (PurchaseQuotation $quotation): array => [
                (int) $quotation->getKey() => $service->emailFileUrl($quotation),
            ])
            ->all();
    }

    private function fileResponse(PurchaseQuotation $quotation): BinaryFileResponse
    {
        return app(PurchaseQuotationFileService::class)->response($quotation);
    }

    private function assertBelongsToRequest(PurchaseRequest $purchaseRequest, PurchaseQuotation $quotation): void
    {
        abort_unless((int) $quotation->purchase_request_id === (int) $purchaseRequest->getKey(), 404);
    }

    /**
     * @param  Collection<int, PurchaseQuotation>  $quotations
     */
    private function assertEmailApprover(Request $request, Collection $quotations): int
    {
        $approverId = (int) $request->query('director', $request->input('director'));

        abort_unless(
            $approverId > 0
                && $quotations->every(fn (PurchaseQuotation $quotation): bool => (int) $quotation->aprobador_id === $approverId),
            403,
        );

        return $approverId;
    }

    /** Los enlaces del formulario usan la misma URL con la que el aprobador abrió el correo. */
    private function forceUrlsFromRequest(Request $request): void
    {
        URL::forceRootUrl($request->root());
        URL::forceScheme($request->getScheme());
    }

    private function backToPurchase(string $module, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        return redirect()->route('purchase-requests.processing.purchase', [
            'module' => $module,
            'purchase_request' => $purchaseRequest->getKey(),
        ]);
    }
}

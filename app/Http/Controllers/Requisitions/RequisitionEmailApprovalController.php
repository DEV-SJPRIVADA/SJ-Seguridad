<?php

namespace App\Http\Controllers\Requisitions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requisitions\EmailManagementApprovalRequest;
use App\Models\PersonalRequisition;
use App\Services\Requisitions\RequisitionEmailApprovalUrlBuilder;
use App\Services\Requisitions\RequisitionManagementApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use InvalidArgumentException;

class RequisitionEmailApprovalController extends Controller
{
    public function show(
        Request $request,
        PersonalRequisition $requisition,
        RequisitionEmailApprovalUrlBuilder $urlBuilder,
    ): View {
        $this->forceUrlsFromRequest($request);
        $requisition->load(['client', 'city', 'clientType', 'position', 'programmingType', 'requestReason', 'requester', 'uniform']);

        $alreadyResolved = $requisition->status !== PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA;

        return view('modules.requisitions.email-approval', [
            'requisition' => $requisition,
            'alreadyResolved' => $alreadyResolved,
            'decideUrl' => $urlBuilder->updateUrl($requisition),
            'statusLabels' => PersonalRequisition::statuses(),
        ]);
    }

    public function update(
        EmailManagementApprovalRequest $request,
        PersonalRequisition $requisition,
        RequisitionManagementApprovalService $approvalService,
        RequisitionEmailApprovalUrlBuilder $urlBuilder,
    ): View|RedirectResponse {
        $this->forceUrlsFromRequest($request);
        $requisition->load(['client', 'city', 'clientType', 'position', 'programmingType', 'requestReason', 'requester', 'uniform']);

        if ($requisition->status !== PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA) {
            return view('modules.requisitions.email-approval', [
                'requisition' => $requisition,
                'alreadyResolved' => true,
                'decideUrl' => $urlBuilder->updateUrl($requisition),
                'statusLabels' => PersonalRequisition::statuses(),
            ]);
        }

        try {
            $approvalService->resolve(
                $requisition,
                $request->validated('action'),
                $request->input('comment'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect($urlBuilder->showUrl($requisition))
                ->withErrors(['action' => $exception->getMessage()])
                ->withInput();
        }

        $requisition->refresh();
        $requisition->load(['client', 'city', 'clientType', 'position', 'programmingType', 'requestReason', 'requester', 'uniform']);

        return view('modules.requisitions.email-approval-result', [
            'requisition' => $requisition,
            'action' => $request->validated('action'),
            'statusLabels' => PersonalRequisition::statuses(),
        ]);
    }

    private function forceUrlsFromRequest(Request $request): void
    {
        URL::forceRootUrl($request->root());
        URL::forceScheme($request->getScheme());
    }
}

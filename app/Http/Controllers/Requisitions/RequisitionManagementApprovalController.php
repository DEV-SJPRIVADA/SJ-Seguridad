<?php

namespace App\Http\Controllers\Requisitions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requisitions\DecideManagementApprovalRequest;
use App\Models\PersonalRequisition;
use App\Services\Requisitions\RequisitionManagementApprovalService;
use App\Traits\HasRequisitionTabs;
use App\Traits\ValidatesModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RequisitionManagementApprovalController extends Controller
{
    use HasRequisitionTabs, ValidatesModule;

    public function __construct(
        private readonly RequisitionManagementApprovalService $approvalService,
    ) {}

    public function index(string $module, Request $request): View
    {
        $this->abortIfUnknownModule($module);

        $filter = $this->approvalService->normalizeFilter($request->string('estado')->toString());

        return view('modules.requisitions.management-approval.index', [
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'requisitions' => $this->approvalService->list($filter),
            'filters' => ['estado' => $filter],
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->getRequisitionSubTabs($module, 'autorizacion_gerencia'),
        ]);
    }

    public function show(string $module, PersonalRequisition $requisition): View
    {
        $this->abortIfUnknownModule($module);
        abort_unless($this->approvalService->passedThroughManagementApproval($requisition), 404);

        $isPending = $requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA;

        return view('modules.requisitions.management-approval.show', [
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'requisition' => $requisition->load(['client', 'city', 'clientType', 'position', 'programmingType', 'requestReason', 'requester', 'uniform', 'statusLogs.author']),
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->getRequisitionSubTabs($module, 'autorizacion_gerencia'),
            'isPending' => $isPending,
        ]);
    }

    public function decide(DecideManagementApprovalRequest $request, string $module, PersonalRequisition $requisition): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        abort_unless($requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA, 404);

        try {
            $this->approvalService->resolve(
                $requisition,
                $request->string('action')->toString(),
                $request->input('comment'),
                $request->user(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['action' => $exception->getMessage()])->withInput();
        }

        $message = $request->string('action')->toString() === 'approve'
            ? 'Requisicion '.$requisition->code.' autorizada. Gestion humana puede continuar.'
            : 'Requisicion '.$requisition->code.' rechazada.';

        return redirect()
            ->route('requisitions.management-approval.index', [
                'module' => $module,
                'estado' => $request->string('action')->toString() === 'approve'
                    ? RequisitionManagementApprovalService::FILTER_APROBADA
                    : RequisitionManagementApprovalService::FILTER_RECHAZADA,
            ])
            ->with('status', $message);
    }
}

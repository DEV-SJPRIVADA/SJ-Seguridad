<?php

namespace App\Http\Controllers\Requisitions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requisitions\DecideManagementApprovalRequest;
use App\Mail\PersonalRequisitionStatusChangedMail;
use App\Models\PersonalRequisition;
use App\Services\Access\RequisitionAccessService;
use App\Traits\HasRequisitionTabs;
use App\Traits\ValidatesModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RequisitionManagementApprovalController extends Controller
{
    use HasRequisitionTabs, ValidatesModule;

    public function __construct(
        private readonly RequisitionAccessService $requisitionAccess,
    ) {}

    public function index(string $module): View
    {
        $this->abortIfUnknownModule($module);

        $requisitions = PersonalRequisition::query()
            ->where('status', PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA)
            ->with(['client', 'position', 'requester', 'requestReason', 'city'])
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->get();

        return view('modules.requisitions.management-approval.index', [
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'requisitions' => $requisitions,
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->getRequisitionSubTabs($module, 'autorizacion_gerencia'),
        ]);
    }

    public function show(string $module, PersonalRequisition $requisition): View
    {
        $this->abortIfUnknownModule($module);
        abort_unless($requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA, 404);

        return view('modules.requisitions.management-approval.show', [
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'requisition' => $requisition->load(['client', 'city', 'clientType', 'position', 'programmingType', 'requestReason', 'requester', 'uniform']),
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->getRequisitionSubTabs($module, 'autorizacion_gerencia'),
        ]);
    }

    public function decide(DecideManagementApprovalRequest $request, string $module, PersonalRequisition $requisition): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        abort_unless($requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA, 404);

        $action = $request->string('action')->toString();
        $oldStatus = $requisition->status;
        $newStatus = $action === 'approve'
            ? PersonalRequisition::STATUS_SOLICITADA
            : PersonalRequisition::STATUS_CANCELADA;

        $comment = $action === 'reject'
            ? (string) $request->input('comment')
            : 'Autorizada por gerencia.';

        DB::transaction(function () use ($request, $requisition, $newStatus, $comment): void {
            $requisition->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
                'closed_at' => $newStatus === PersonalRequisition::STATUS_CANCELADA ? now() : null,
            ]);

            $requisition->statusLogs()->create([
                'from_status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA,
                'to_status' => $newStatus,
                'changed_by' => $request->user()->id,
                'comment' => $comment,
            ]);
        });

        if ($newStatus === PersonalRequisition::STATUS_CANCELADA) {
            try {
                $requisition->loadMissing('requester');
                if (filled($requisition->requester?->email)) {
                    Mail::to($requisition->requester)->send(
                        new PersonalRequisitionStatusChangedMail($requisition->fresh(), $oldStatus, $newStatus)
                    );
                }
            } catch (\Exception $e) {
                Log::error('Error enviando correo de rechazo gerencia: '.$e->getMessage());
            }
        }

        $message = $action === 'approve'
            ? 'Requisicion '.$requisition->code.' autorizada. Gestion humana puede continuar.'
            : 'Requisicion '.$requisition->code.' rechazada.';

        return redirect()
            ->route('requisitions.management-approval.index', ['module' => $module])
            ->with('status', $message);
    }
}

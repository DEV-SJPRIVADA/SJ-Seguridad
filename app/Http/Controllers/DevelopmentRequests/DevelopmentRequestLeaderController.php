<?php

namespace App\Http\Controllers\DevelopmentRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\DevelopmentRequests\LeaderDecisionDevelopmentRequestRequest;
use App\Models\DevelopmentRequest;
use App\Services\DevelopmentRequests\DevelopmentRequestWorkflowService;
use App\Traits\HasDevelopmentRequestTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DevelopmentRequestLeaderController extends Controller
{
    use HasDevelopmentRequestTabs;

    public function index(string $module): View
    {
        $user = auth()->user();

        $query = DevelopmentRequest::query()
            ->with(['creator', 'leader'])
            ->where('status', DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER)
            ->latest();

        if ($user && ! $user->hasRole('super-admin') && ! $user->can('manage.users')) {
            $query->where('leader_id', $user->id);
        }

        return view('modules.development-requests.leader-approval', [
            'module' => $module,
            'subTabs' => $this->getDevelopmentRequestSubTabs($module),
            'requests' => $query->get(),
        ]);
    }

    public function update(
        LeaderDecisionDevelopmentRequestRequest $request,
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestWorkflowService $workflow,
    ): RedirectResponse {
        $validated = $request->validated();

        $workflow->leaderDecide(
            $developmentRequest,
            $request->user(),
            (string) $validated['decision'],
            $validated['notes'] ?? null,
        );

        $msg = $validated['decision'] === 'approve'
            ? 'Solicitud aprobada y radicada.'
            : 'Solicitud rechazada.';

        return redirect()
            ->route('development-requests.leader-approval', ['module' => $module])
            ->with('status', $msg);
    }
}

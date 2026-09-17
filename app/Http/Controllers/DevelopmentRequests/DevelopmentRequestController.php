<?php

namespace App\Http\Controllers\DevelopmentRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\DevelopmentRequests\StoreDevelopmentRequestRequest;
use App\Http\Requests\DevelopmentRequests\UpdateDevelopmentRequestRequest;
use App\Models\DevelopmentRequest;
use App\Models\DevelopmentRequestAttachment;
use App\Services\Access\DevelopmentRequestAccessService;
use App\Services\DevelopmentRequests\DevelopmentRequestAttachmentService;
use App\Services\DevelopmentRequests\DevelopmentRequestWorkflowService;
use App\Traits\HasDevelopmentRequestTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevelopmentRequestController extends Controller
{
    use HasDevelopmentRequestTabs;

    public function index(string $module, DevelopmentRequestAccessService $access): RedirectResponse|View
    {
        $user = auth()->user();

        abort_unless(
            $user && $access->canViewDevelopmentRequestBoard($user, $module),
            403
        );

        $tabs = $access->visibleTabsFor($user, $module);

        if ($tabs === []) {
            abort_unless($access->canView($user), 403);

            return view('modules.development-requests.index', [
                'module' => $module,
                'subTabs' => collect(),
            ]);
        }

        $first = $tabs[0];

        return match ($first) {
            'nueva' => redirect()->route('development-requests.create', ['module' => $module]),
            'mis_solicitudes' => redirect()->route('development-requests.my-requests', ['module' => $module]),
            'aprobacion_lider' => redirect()->route('development-requests.leader-approval', ['module' => $module]),
            'bandeja_tic' => redirect()->route('development-requests.tic-queue', ['module' => $module]),
            default => view('modules.development-requests.index', [
                'module' => $module,
                'subTabs' => $this->getDevelopmentRequestSubTabs($module),
            ]),
        };
    }

    public function create(string $module, DevelopmentRequestAccessService $access): View
    {
        $user = auth()->user();

        return view('modules.development-requests.create', [
            'module' => $module,
            'subTabs' => $this->getDevelopmentRequestSubTabs($module),
            'leaders' => $access->leadersQuery()->get(['id', 'name', 'email']),
            'areas' => config('access.areas', []),
            'tipoOptions' => $this->optionsFromLabels(DevelopmentRequest::tiposLabels()),
            'prioridadOptions' => $this->optionsFromLabels(DevelopmentRequest::prioridadesLabels()),
            'defaultRequester' => [
                'name' => $user?->name,
                'email' => $user?->email,
            ],
        ]);
    }

    public function store(
        StoreDevelopmentRequestRequest $request,
        string $module,
        DevelopmentRequestAccessService $access,
        DevelopmentRequestWorkflowService $workflow,
        DevelopmentRequestAttachmentService $attachments,
    ): RedirectResponse {
        $user = $request->user();
        $validated = $request->validated();
        $leader = $access->leadersQuery()->whereKey($validated['leader_id'])->first();

        if ($leader === null) {
            return back()->withErrors(['leader_id' => 'Seleccione un lider valido.'])->withInput();
        }

        $payload = $this->payloadFromValidated($validated);
        $actorIsLeader = (int) $user->id === (int) $leader->id;

        $developmentRequest = $workflow->createDraftOrSubmit(
            $payload,
            $user,
            (string) $validated['action'],
            $actorIsLeader,
        );

        $files = $attachments->filesFromRequest($request);
        if ($files !== []) {
            $attachments->storeMany($developmentRequest, $files);
        }

        $message = $developmentRequest->status === DevelopmentRequest::STATUS_RADICADO
            ? 'Solicitud radicada correctamente ('.$developmentRequest->code.').'
            : ($developmentRequest->status === DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER
                ? 'Solicitud enviada a aprobacion del lider.'
                : 'Borrador guardado.');

        return redirect()
            ->route('development-requests.show', [
                'module' => $module,
                'development_request' => $developmentRequest,
                'from' => 'mis_solicitudes',
            ])
            ->with('status', $message);
    }

    public function myRequests(string $module): View
    {
        $requests = DevelopmentRequest::query()
            ->with(['leader', 'creator'])
            ->where('created_by', auth()->id())
            ->latest()
            ->get();

        return view('modules.development-requests.my-requests', [
            'module' => $module,
            'subTabs' => $this->getDevelopmentRequestSubTabs($module),
            'requests' => $requests,
        ]);
    }

    public function edit(
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestAccessService $access,
    ): View {
        Gate::authorize('update', $developmentRequest);

        return view('modules.development-requests.edit', [
            'module' => $module,
            'subTabs' => $this->getDevelopmentRequestSubTabs($module),
            'developmentRequest' => $developmentRequest->load('attachments'),
            'leaders' => $access->leadersQuery()->get(['id', 'name', 'email']),
            'areas' => config('access.areas', []),
            'tipoOptions' => $this->optionsFromLabels(DevelopmentRequest::tiposLabels()),
            'prioridadOptions' => $this->optionsFromLabels(DevelopmentRequest::prioridadesLabels()),
        ]);
    }

    public function update(
        UpdateDevelopmentRequestRequest $request,
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestAccessService $access,
        DevelopmentRequestWorkflowService $workflow,
        DevelopmentRequestAttachmentService $attachments,
    ): RedirectResponse {
        Gate::authorize('update', $developmentRequest);

        $user = $request->user();
        $validated = $request->validated();
        $leader = $access->leadersQuery()->whereKey($validated['leader_id'])->first();

        if ($leader === null) {
            return back()->withErrors(['leader_id' => 'Seleccione un lider valido.'])->withInput();
        }

        $payload = $this->payloadFromValidated($validated);
        $actorIsLeader = (int) $user->id === (int) $leader->id;

        $developmentRequest = $workflow->updateDraftOrResubmit(
            $developmentRequest,
            $payload,
            $user,
            (string) $validated['action'],
            $actorIsLeader,
        );

        $files = $attachments->filesFromRequest($request);
        if ($files !== []) {
            $start = ((int) $developmentRequest->attachments()->max('sort_order')) + 1;
            $attachments->storeMany($developmentRequest, $files, startSortOrder: $start);
        }

        return redirect()
            ->route('development-requests.show', [
                'module' => $module,
                'development_request' => $developmentRequest,
                'from' => 'mis_solicitudes',
            ])
            ->with('status', 'Solicitud actualizada.');
    }

    public function show(
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestWorkflowService $workflow,
    ): View {
        Gate::authorize('view', $developmentRequest);

        $developmentRequest->load([
            'creator',
            'leader',
            'assignedProgrammer',
            'attachments',
            'statusLogs.user',
            'messages.user',
        ]);

        $user = auth()->user();

        return view('modules.development-requests.show', [
            'module' => $module,
            'subTabs' => $this->getDevelopmentRequestSubTabs($module),
            'developmentRequest' => $developmentRequest,
            'from' => request()->query('from', $this->resolveDevelopmentRequestShowTabContext()),
            'canComment' => $user?->can('comment', $developmentRequest) ?? false,
            'canLeaderDecide' => $developmentRequest->status === DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER
                && $user?->can('devreq.tab.leader_approval')
                && ((int) $developmentRequest->leader_id === (int) $user?->id
                    || $user?->hasRole('super-admin')
                    || $user?->can('manage.users')),
            'canProcessTic' => $user?->can('devreq.tab.tic_queue')
                || $user?->hasRole('super-admin')
                || $user?->can('manage.users'),
            'canUat' => $developmentRequest->status === DevelopmentRequest::STATUS_EN_PRUEBAS
                && ((int) $developmentRequest->created_by === (int) $user?->id
                    || $user?->hasRole('super-admin')
                    || $user?->can('manage.users')),
            'allowedTransitions' => $workflow->allowedTransitions($developmentRequest->status),
            'programmerOptions' => DevelopmentRequestTicController::programmerOptions(),
            'prioridadOptions' => $this->optionsFromLabels(DevelopmentRequest::prioridadesLabels()),
        ]);
    }

    public function downloadAttachment(
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestAttachment $attachment,
    ): StreamedResponse {
        Gate::authorize('view', $developmentRequest);
        abort_unless((int) $attachment->development_request_id === (int) $developmentRequest->id, 404);

        $disk = (string) config('development-requests.attachments.disk', 'local');

        abort_unless(Storage::disk($disk)->exists($attachment->stored_path), 404);

        return Storage::disk($disk)->download($attachment->stored_path, $attachment->original_name);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payloadFromValidated(array $validated): array
    {
        return [
            'area_key' => $validated['area_key'],
            'request_type' => $validated['request_type'],
            'title' => $validated['title'],
            'associated_norm' => $validated['associated_norm'] ?? null,
            'proceso_sede' => $validated['proceso_sede'] ?? null,
            'requester_name' => $validated['requester_name'],
            'requester_position' => $validated['requester_position'] ?? null,
            'requester_email' => $validated['requester_email'],
            'requester_phone' => $validated['requester_phone'] ?? null,
            'leader_id' => (int) $validated['leader_id'],
            'description' => $validated['description'],
            'current_process_problem' => $validated['current_process_problem'],
            'desired_steps' => $validated['desired_steps'],
            'users_description' => $validated['users_description'],
            'restrictions' => $validated['restrictions'],
            'scope_in' => $validated['scope_in'],
            'scope_out' => $validated['scope_out'] ?? null,
            'acceptance_criteria' => $validated['acceptance_criteria'],
            'reports' => $validated['reports'] ?? null,
            'suggested_priority' => $validated['suggested_priority'],
            'desired_date' => $validated['desired_date'] ?? null,
            'desired_date_justification' => $validated['desired_date_justification'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @return list<array{value: string, label: string}>
     */
    private function optionsFromLabels(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requisitions\StorePersonalRequisitionRequest;
use App\Http\Requests\Requisitions\StoreRequisitionParameterRequest;
use App\Http\Requests\Requisitions\UpdatePersonalRequisitionRequest;
use App\Models\PersonalRequisition;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequisitionController extends Controller
{
    /**
     * @var array<string, array{label: string, model: class-string<\Illuminate\Database\Eloquent\Model>}>
     */
    private const PARAMETER_TYPES = [
        'positions' => ['label' => 'Cargos solicitados', 'model' => RequisitionPosition::class],
        'reasons' => ['label' => 'Motivos de solicitud', 'model' => RequisitionRequestReason::class],
        'clients' => ['label' => 'Clientes', 'model' => RequisitionClient::class],
        'cities' => ['label' => 'Ciudades', 'model' => RequisitionCity::class],
        'client-types' => ['label' => 'Tipos de cliente', 'model' => RequisitionClientType::class],
        'programming-types' => ['label' => 'Tipos de programacion', 'model' => RequisitionProgrammingType::class],
    ];

    public function dashboard(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeBoardAccess($module);

        $isHR = auth()->user()?->can('manage.area.gestion_humana') || auth()->user()?->can('manage.requisitions');

        $requisitions = PersonalRequisition::query()
            ->when(! $isHR, fn ($q) => $q->where('requesting_area_key', $module))
            ->latest()
            ->with(['client', 'position', 'requester'])
            ->get();

        return view('requisitions.dashboard', [
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->subTabs($module, 'dashboard'),
            'stats' => [
                'total' => $requisitions->count(),
                'solicitada' => $requisitions->where('status', PersonalRequisition::STATUS_SOLICITADA)->count(),
                'en_gestion' => $requisitions->where('status', PersonalRequisition::STATUS_EN_GESTION)->count(),
                'contratado' => $requisitions->where('status', PersonalRequisition::STATUS_CONTRATADO)->count(),
                'cancelada' => $requisitions->where('status', PersonalRequisition::STATUS_CANCELADA)->count(),
            ],
            'latestRequisitions' => $requisitions->take(8),
        ]);
    }

    public function create(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeRequestCreation($module);

        return view('requisitions.create', [
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'subTabs' => $this->subTabs($module, 'solicitar'),
            'catalogs' => $this->catalogs(),
            'sexOptions' => $this->sexOptions(),
            'areaOptions' => config('access.areas'),
        ]);
    }

    public function store(StorePersonalRequisitionRequest $request, string $module): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeRequestCreation($module);

        $requisition = DB::transaction(function () use ($request, $module): PersonalRequisition {
            $requisition = PersonalRequisition::create([
                'code' => $this->nextCode(),
                'requested_by' => $request->user()->id,
                'request_date' => now()->toDateString(),
                'leader_name' => $request->user()->name,
                'requesting_area_key' => $module,
                'position_id' => $request->integer('position_id'),
                'sex' => $request->string('sex')->toString(),
                'quantity' => $request->integer('quantity'),
                'replacement_document' => $request->input('replacement_document'),
                'replacement_name' => $request->input('replacement_name'),
                'operating_area_key' => $request->string('operating_area_key')->toString(),
                'request_reason_id' => $request->integer('request_reason_id'),
                'client_id' => $request->integer('client_id'),
                'city_id' => $request->integer('city_id'),
                'client_type_id' => $request->integer('client_type_id'),
                'programming_type_id' => $request->integer('programming_type_id'),
                'required_profile' => $request->string('required_profile')->toString(),
                'required_uniform' => $request->input('required_uniform'),
                'cost_center' => $request->input('cost_center'),
                'requester_observation' => $request->input('requester_observation'),
                'status' => PersonalRequisition::STATUS_SOLICITADA,
                'status_changed_at' => now(),
            ]);

            $requisition->statusLogs()->create([
                'from_status' => null,
                'to_status' => PersonalRequisition::STATUS_SOLICITADA,
                'changed_by' => $request->user()->id,
                'comment' => 'Solicitud creada desde el tablero de requisiciones.',
            ]);

            return $requisition;
        });

        return redirect()
            ->route('requisitions.dashboard', ['module' => $module])
            ->with('status', 'requisition-created')
            ->with('requisition_code', $requisition->code);
    }

    public function manage(Request $request, string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManagement();

        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $isHR = auth()->user()?->can('manage.area.gestion_humana') || auth()->user()?->can('manage.requisitions');

        $requisitions = PersonalRequisition::query()
            ->when(! $isHR, fn ($q) => $q->where('requesting_area_key', $module))
            ->with(['client', 'position', 'requester'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('leader_name', 'like', "%{$search}%")
                        ->orWhere('required_profile', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('requisitions.manage', [
            'filters' => ['q' => $search, 'status' => $status],
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'requisitions' => $requisitions,
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->subTabs($module, 'gestion'),
        ]);
    }

    public function edit(string $module, PersonalRequisition $requisition): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManagement();
        $isHR = auth()->user()?->can('manage.area.gestion_humana') || auth()->user()?->can('manage.requisitions');
        abort_unless($isHR || $requisition->requesting_area_key === $module, 404);

        return view('requisitions.edit', [
            'areaOptions' => config('access.areas'),
            'catalogs' => $this->catalogs(),
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'requisition' => $requisition->load(['client', 'city', 'clientType', 'position', 'programmingType', 'requestReason', 'requester', 'statusLogs.author']),
            'sexOptions' => $this->sexOptions(),
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->subTabs($module, 'gestion'),
        ]);
    }

    public function update(UpdatePersonalRequisitionRequest $request, string $module, PersonalRequisition $requisition): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManagement();
        $isHR = auth()->user()?->can('manage.area.gestion_humana') || auth()->user()?->can('manage.requisitions');
        abort_unless($isHR || $requisition->requesting_area_key === $module, 404);

        DB::transaction(function () use ($request, $requisition): void {
            $oldStatus = $requisition->status;
            $newStatus = $request->string('status')->toString();

            $requisition->update([
                'managed_by' => $request->user()->id,
                'position_id' => $request->integer('position_id'),
                'sex' => $request->string('sex')->toString(),
                'quantity' => $request->integer('quantity'),
                'replacement_document' => $request->input('replacement_document'),
                'replacement_name' => $request->input('replacement_name'),
                'operating_area_key' => $request->string('operating_area_key')->toString(),
                'request_reason_id' => $request->integer('request_reason_id'),
                'client_id' => $request->integer('client_id'),
                'city_id' => $request->integer('city_id'),
                'client_type_id' => $request->integer('client_type_id'),
                'programming_type_id' => $request->integer('programming_type_id'),
                'required_profile' => $request->string('required_profile')->toString(),
                'required_uniform' => $request->input('required_uniform'),
                'cost_center' => $request->input('cost_center'),
                'requester_observation' => $request->input('requester_observation'),
                'human_resources_observation' => $request->input('human_resources_observation'),
                'status' => $newStatus,
                'status_changed_at' => $oldStatus !== $newStatus ? now() : $requisition->status_changed_at,
                'closed_at' => in_array($newStatus, [PersonalRequisition::STATUS_CONTRATADO, PersonalRequisition::STATUS_CANCELADA], true) ? now() : null,
            ]);

            if ($oldStatus !== $newStatus) {
                $requisition->statusLogs()->create([
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'changed_by' => $request->user()->id,
                    'comment' => $request->input('human_resources_observation'),
                ]);
            }
        });

        return redirect()
            ->route('requisitions.edit', ['module' => $module, 'requisition' => $requisition])
            ->with('status', 'requisition-updated');
    }

    public function parameters(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeParameterManagement();

        $catalogs = collect(self::PARAMETER_TYPES)
            ->map(function (array $definition, string $type): array {
                $modelClass = $definition['model'];

                return [
                    'key' => $type,
                    'label' => $definition['label'],
                    'items' => $modelClass::query()->orderBy('sort_order')->orderBy('name')->get(),
                ];
            })
            ->values();

        return view('requisitions.parameters', [
            'catalogs' => $catalogs,
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'subTabs' => $this->subTabs($module, 'parametros'),
        ]);
    }

    public function storeParameter(StoreRequisitionParameterRequest $request, string $module, string $type): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeParameterManagement();

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $modelClass = $definition['model'];

        $modelClass::query()->firstOrCreate(
            ['name' => Str::of($request->string('name')->toString())->trim()->squish()->toString()],
            [
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->integer('sort_order'),
            ]
        );

        return redirect()
            ->route('requisitions.parameters', ['module' => $module])
            ->with('status', 'requisition-parameter-created');
    }

    public function updateParameter(StoreRequisitionParameterRequest $request, string $module, string $type, int $parameterId): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeParameterManagement();

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $record = $definition['model']::query()->findOrFail($parameterId);

        $record->update([
            'name'       => Str::of($request->string('name')->toString())->trim()->squish()->toString(),
            'is_active'  => $request->boolean('is_active'),
            'sort_order' => $request->integer('sort_order'),
        ]);

        return redirect()
            ->route('requisitions.parameters', ['module' => $module])
            ->with('status', 'requisition-parameter-updated');
    }

    public function destroyParameter(string $module, string $type, int $parameterId): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeParameterManagement();

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $definition['model']::query()->findOrFail($parameterId)->delete();

        return redirect()
            ->route('requisitions.parameters', ['module' => $module])
            ->with('status', 'requisition-parameter-deleted');
    }

    private function abortIfUnknownModule(string $module): void
    {
        abort_unless(array_key_exists($module, config('access.areas', [])), 404);
    }

    private function authorizeBoardAccess(string $module): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->can("view.board.{$module}.requisiciones")
            || $user?->can("view.area.{$module}")
            || $user?->can("manage.area.{$module}")
            || $user?->can('manage.requisitions')
            || $user?->can('manage.requisition.parameters')
            || $user?->can('manage.users')
            || $user?->can('manage.area.gestion_humana'),
            403
        );
    }

    private function authorizeRequestCreation(string $module): void
    {
        $this->authorizeBoardAccess($module);

        $user = auth()->user();

        // Usuarios con gestion global no necesitan tener area_key coincidente
        $canManageAll = $user?->can('manage.users') || $user?->can('manage.area.gestion_humana');

        abort_unless($canManageAll || $user?->area_key === $module, 403);
    }

    private function authorizeManagement(): void
    {
        abort_unless(
            auth()->user()?->can('manage.requisitions')
            || auth()->user()?->can('manage.area.gestion_humana'),
            403
        );
    }

    private function authorizeParameterManagement(): void
    {
        abort_unless(
            auth()->user()?->can('manage.requisition.parameters')
            || auth()->user()?->can('manage.users')
            || auth()->user()?->can('manage.area.gestion_humana'),
            403
        );
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>>
     */
    private function catalogs(): array
    {
        return [
            'positions' => RequisitionPosition::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'reasons' => RequisitionRequestReason::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'clients' => RequisitionClient::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'cities' => RequisitionCity::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'clientTypes' => RequisitionClientType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'programmingTypes' => RequisitionProgrammingType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sexOptions(): array
    {
        return [
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            'indiferente' => 'Indiferente',
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, url: string, active: bool}>
     */
    private function subTabs(string $module, string $activeKey): array
    {
        $user = auth()->user();

        return $user->requisitionBoardTabsFor($module)
            ->map(function (string $tabKey) use ($activeKey, $module): array {
                $routes = [
                    'dashboard' => route('requisitions.dashboard', ['module' => $module]),
                    'solicitar' => route('requisitions.create', ['module' => $module]),
                    'gestion' => route('requisitions.manage', ['module' => $module]),
                    'parametros' => route('requisitions.parameters', ['module' => $module]),
                ];

                return [
                    'key' => $tabKey,
                    'label' => config("access.requisition_tabs.{$tabKey}", Str::headline($tabKey)),
                    'url' => $routes[$tabKey],
                    'active' => $tabKey === $activeKey,
                ];
            })
            ->values()
            ->all();
    }

    private function nextCode(): string
    {
        $year = now()->format('Y');
        $lastCode = PersonalRequisition::query()
            ->where('code', 'like', "REQ-{$year}-%")
            ->orderByDesc('id')
            ->value('code');

        if ($lastCode) {
            $parts = explode('-', $lastCode);
            $lastNumber = (int) end($parts);
        } else {
            $lastNumber = 0;
        }

        return 'REQ-'.$year.'-'.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}

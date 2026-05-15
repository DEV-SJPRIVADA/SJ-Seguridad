<?php

namespace App\Http\Controllers\Requisitions;

use App\Http\Controllers\Controller;
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
use App\Models\RequisitionContractType;
use App\Models\RequisitionUniform;
use App\Models\RequisitionRecruiter;
use App\Models\RequisitionNotificationEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PersonalRequisitionNotification;
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
        'uniforms' => ['label' => 'Dotación requerida', 'model' => RequisitionUniform::class],
        'contract-types' => ['label' => 'Tipos de contrato', 'model' => RequisitionContractType::class],
        'recruiters' => ['label' => 'Encargados de selección', 'model' => RequisitionRecruiter::class],
        'emails' => ['label' => 'Correos de notificación', 'model' => RequisitionNotificationEmail::class],
    ];

    public function dashboard(Request $request, string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeBoardAccess($module);

        $isHR = \Illuminate\Support\Facades\Auth::user()?->can('manage.area.gestion_humana') || \Illuminate\Support\Facades\Auth::user()?->can('manage.requisitions');

        // Filtros
        $filters = [
            'client_id' => $request->input('client_id'),
            'position_id' => $request->input('position_id'),
            'city_id' => $request->input('city_id'),
            'status' => $request->input('status'),
            'year' => $request->input('year', now()->year),
            'month' => $request->input('month'),
        ];

        $query = PersonalRequisition::query()
            ->when(! $isHR, fn ($q) => $q->where('requesting_area_key', $module))
            ->when($filters['client_id'], fn($q) => $q->where('client_id', $filters['client_id']))
            ->when($filters['position_id'], fn($q) => $q->where('position_id', $filters['position_id']))
            ->when($filters['city_id'], fn($q) => $q->where('city_id', $filters['city_id']))
            ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
            ->when($filters['year'], fn($q) => $q->whereYear('request_date', $filters['year']))
            ->when($filters['month'], fn($q) => $q->whereMonth('request_date', $filters['month']));

        $requisitions = $query->get();

        // Datos para Gráficos
        $statsByStatus = $requisitions->groupBy('status')->map->count();
        $statsByMonth = $requisitions->groupBy(fn($r) => \Carbon\Carbon::parse($r->request_date)->format('n'))
            ->map->count();
        
        $statsByCity = $requisitions->groupBy('city_id')->map->count()->sortDesc()->take(5);
        $cityNames = RequisitionCity::whereIn('id', $statsByCity->keys())->pluck('name', 'id');

        $statsByClient = $requisitions->groupBy('client_id')->map->count()->sortDesc()->take(5);
        $clientNames = RequisitionClient::whereIn('id', $statsByClient->keys())->pluck('name', 'id');

        return view('modules.requisitions.dashboard', [
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->subTabs($module, 'dashboard'),
            'filters' => $filters,
            'catalogs' => $this->catalogs(),
            'stats' => [
                'total' => $requisitions->count(),
                'solicitada' => $statsByStatus->get(PersonalRequisition::STATUS_SOLICITADA, 0),
                'en_gestion' => $statsByStatus->get(PersonalRequisition::STATUS_EN_GESTION, 0),
                'contratado' => $statsByStatus->get(PersonalRequisition::STATUS_CONTRATADO, 0),
                'cancelada' => $statsByStatus->get(PersonalRequisition::STATUS_CANCELADA, 0),
            ],
            'chartData' => [
                'status' => [
                    'labels' => collect(PersonalRequisition::statuses())->only($statsByStatus->keys())->values(),
                    'data' => $statsByStatus->values(),
                ],
                'trend' => [
                    'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    'data' => collect(range(1, 12))->map(fn($m) => $statsByMonth->get($m, 0)),
                ],
                'cities' => [
                    'labels' => $statsByCity->keys()->map(fn($id) => $cityNames[$id] ?? 'Desconocida'),
                    'data' => $statsByCity->values(),
                ],
                'clients' => [
                    'labels' => $statsByClient->keys()->map(fn($id) => $clientNames[$id] ?? 'Desconocido'),
                    'data' => $statsByClient->values(),
                ]
            ]
        ]);
    }

    public function create(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeRequestCreation($module);

        return view('modules.requisitions.create', [
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

        $requisitions = DB::transaction(function () use ($request, $module): array {
            $quantity = $request->integer('quantity', 1);
            $created = [];
            
            // Obtener el numero base para el codigo una sola vez
            $year = now()->format('Y');
            $lastCode = PersonalRequisition::query()
                ->where('code', 'like', "REQ-{$year}-%")
                ->orderByDesc('id')
                ->value('code');

            $lastNumber = 0;
            if ($lastCode) {
                $parts = explode('-', $lastCode);
                $lastNumber = (int) end($parts);
            }

            for ($i = 0; $i < $quantity; $i++) {
                $currentNumber = $lastNumber + $i + 1;
                $newCode = 'REQ-'.$year.'-'.str_pad((string) $currentNumber, 4, '0', STR_PAD_LEFT);

                $requisition = PersonalRequisition::create([
                    'code' => $newCode,
                    'requested_by' => $request->user()->id,
                    'request_date' => now()->toDateString(),
                    'leader_name' => $request->user()->name,
                    'requesting_area_key' => $module,
                    'position_id' => $request->integer('position_id'),
                    'sex' => $request->string('sex')->toString(),
                    'quantity' => 1, // Ahora cada registro es individual
                    'replacement_document' => $request->input('replacement_document'),
                    'replacement_name' => $request->input('replacement_name'),
                    'operating_area_key' => $request->string('operating_area_key')->toString(),
                    'request_reason_id' => $request->integer('request_reason_id'),
                    'client_id' => $request->integer('client_id'),
                    'city_id' => $request->integer('city_id'),
                    'client_type_id' => $request->integer('client_type_id'),
                    'programming_type_id' => $request->integer('programming_type_id'),
                    'required_profile' => $request->string('required_profile')->toString(),
                    'uniform_id' => $request->integer('uniform_id'),
                    'contract_type_id' => $request->filled('contract_type_id') ? $request->integer('contract_type_id') : null,
                    'contract_duration' => $request->input('contract_duration'),
                    'base_salary' => $request->input('base_salary'),
                    'transport_allowance' => $request->input('transport_allowance'),
                    'mobility_allowance' => $request->input('mobility_allowance'),
                    'statutory_bonus' => $request->input('statutory_bonus'),
                    'non_statutory_bonus' => $request->input('non_statutory_bonus'),
                    'other_allowances' => $request->input('other_allowances'),
                    'leasing_contract' => $request->input('leasing_contract'),
                    'cost_center' => $request->input('cost_center'),
                    'recruiter_id' => $request->filled('recruiter_id') ? $request->integer('recruiter_id') : null,
                    'requester_observation' => $request->input('requester_observation'),
                    'status' => PersonalRequisition::STATUS_SOLICITADA,
                    'status_changed_at' => now(),
                ]);

                $requisition->statusLogs()->create([
                    'from_status' => null,
                    'to_status' => PersonalRequisition::STATUS_SOLICITADA,
                    'changed_by' => $request->user()->id,
                    'comment' => 'Solicitud creada (' . ($i + 1) . '/' . $quantity . ') desde el tablero de requisiciones.',
                ]);

                $created[] = $requisition;
            }

            return $created;
        });

        // Envío de notificaciones por correo (Un solo correo consolidado por solicitud)
        try {
            $notificationEmails = RequisitionNotificationEmail::where('is_active', true)->pluck('name')->toArray();
            
            // Si no hay correos parametrizados, usamos el correo base de desarrollo
            if (empty($notificationEmails)) {
                $notificationEmails = ['desarrollo.tic@sjsp.com.co'];
            }

            // Enviamos un solo correo informando de la solicitud completa
            if (!empty($requisitions)) {
                $mainRequisition = $requisitions[0];
                $totalCount = count($requisitions);
                
                Mail::to($notificationEmails)->send(new PersonalRequisitionNotification($mainRequisition, $totalCount));
            }
        } catch (\Exception $e) {
            // Logueamos el error pero permitimos que la app continúe para no bloquear al usuario
            \Illuminate\Support\Facades\Log::error("Error enviando correos de requisición: " . $e->getMessage());
        }

        return redirect()
            ->route('requisitions.dashboard', ['module' => $module])
            ->with('status', 'requisition-created');
    }

    public function manage(Request $request, string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManagement($module);

        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $isHR = \Illuminate\Support\Facades\Auth::user()?->can('manage.area.gestion_humana') || \Illuminate\Support\Facades\Auth::user()?->can('manage.requisitions');

        $requisitions = PersonalRequisition::query()
            ->when(! $isHR, fn ($q) => $q->where('requesting_area_key', $module))
            ->with(['client', 'position', 'requester'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('leader_name', 'like', "%{$search}%")
                        ->orWhere('required_profile', 'like', "%{$search}%")
                        ->orWhere('replacement_name', 'like', "%{$search}%")
                        ->orWhereHas('position', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('city', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('modules.requisitions.manage', [
            'filters' => ['q' => $search, 'status' => $status],
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'requisitions' => $requisitions,
            'statusLabels' => PersonalRequisition::statuses(),
            'subTabs' => $this->subTabs($module, 'gestion'),
        ]);
    }

    public function print(string $module, PersonalRequisition $requisition): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeBoardAccess($module); // Permitir a cualquier usuario que pueda ver el tablero
        
        $isHR = \Illuminate\Support\Facades\Auth::user()?->can('manage.area.gestion_humana') || \Illuminate\Support\Facades\Auth::user()?->can('manage.requisitions');
        abort_unless($isHR || $requisition->requesting_area_key === $module, 404);

        return view('modules.requisitions.print', [
            'requisition' => $requisition->load(['client', 'city', 'clientType', 'position', 'programmingType', 'requestReason', 'requester', 'contractType', 'uniform']),
            'statusLabels' => PersonalRequisition::statuses(),
            'moduleLabel' => config("access.areas.{$requisition->requesting_area_key}"),
        ]);
    }

    public function edit(string $module, PersonalRequisition $requisition): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManagement($module);
        $isHR = \Illuminate\Support\Facades\Auth::user()?->can('manage.area.gestion_humana') || \Illuminate\Support\Facades\Auth::user()?->can('manage.requisitions');
        abort_unless($isHR || $requisition->requesting_area_key === $module, 404);

        return view('modules.requisitions.edit', [
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
        $this->authorizeManagement($module);
        $isHR = \Illuminate\Support\Facades\Auth::user()?->can('manage.area.gestion_humana') || \Illuminate\Support\Facades\Auth::user()?->can('manage.requisitions');
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
                'uniform_id' => $request->integer('uniform_id'),
                'contract_type_id' => $request->filled('contract_type_id') ? $request->integer('contract_type_id') : null,
                'contract_duration' => $request->input('contract_duration'),
                'base_salary' => $request->input('base_salary'),
                'transport_allowance' => $request->input('transport_allowance'),
                'mobility_allowance' => $request->input('mobility_allowance'),
                'statutory_bonus' => $request->input('statutory_bonus'),
                'non_statutory_bonus' => $request->input('non_statutory_bonus'),
                'other_allowances' => $request->input('other_allowances'),
                'leasing_contract' => $request->input('leasing_contract'),
                'recruiter_id' => $request->filled('recruiter_id') ? $request->integer('recruiter_id') : null,
                'cost_center' => $request->input('cost_center'),
                'requester_observation' => $request->input('requester_observation'),
                'human_resources_observation' => $request->input('human_resources_observation'),
                'recruiter_name' => $request->input('recruiter_name'),
                'hiring_date' => $request->input('hiring_date'),
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
        $this->authorizeParameterManagement($module);

        $catalogs = collect(self::PARAMETER_TYPES)
            ->map(function (array $definition, string $type): array {
                $modelClass = $definition['model'];

                return [
                    'key' => $type,
                    'label' => $definition['label'],
                    'items' => $modelClass::query()->orderBy('name')->get(),
                ];
            })
            ->values();

        return view('modules.requisitions.parameters', [
            'catalogs' => $catalogs,
            'moduleKey' => $module,
            'moduleLabel' => config("access.areas.{$module}"),
            'subTabs' => $this->subTabs($module, 'parametros'),
        ]);
    }

    public function storeParameter(StoreRequisitionParameterRequest $request, string $module, string $type): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeParameterManagement($module);

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $modelClass = $definition['model'];

        $modelClass::query()->firstOrCreate(
            ['name' => Str::of($request->string('name')->toString())->trim()->squish()->toString()],
            [
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return redirect()
            ->route('requisitions.parameters', ['module' => $module])
            ->with('status', 'requisition-parameter-created');
    }

    public function updateParameter(StoreRequisitionParameterRequest $request, string $module, string $type, int $parameterId): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeParameterManagement($module);

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $record = $definition['model']::query()->findOrFail($parameterId);

        $record->update([
            'name'       => Str::of($request->string('name')->toString())->trim()->squish()->toString(),
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('requisitions.parameters', ['module' => $module])
            ->with('status', 'requisition-parameter-updated');
    }

    public function destroyParameter(string $module, string $type, int $parameterId): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeParameterManagement($module);

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

    private function authorizeManagement(string $module): void
    {
        abort_unless($module === 'gestion_humana', 403);

        abort_unless(
            auth()->user()?->can('manage.requisitions')
            || auth()->user()?->can('manage.area.gestion_humana'),
            403
        );
    }

    private function authorizeParameterManagement(string $module): void
    {
        abort_unless($module === 'gestion_humana', 403);

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
            'positions' => RequisitionPosition::query()->where('is_active', true)->orderBy('name')->get(),
            'reasons' => RequisitionRequestReason::query()->where('is_active', true)->orderBy('name')->get(),
            'clients' => RequisitionClient::query()->where('is_active', true)->orderBy('name')->get(),
            'cities' => RequisitionCity::query()->where('is_active', true)->orderBy('name')->get(),
            'clientTypes' => RequisitionClientType::query()->where('is_active', true)->orderBy('name')->get(),
            'programmingTypes' => RequisitionProgrammingType::query()->where('is_active', true)->orderBy('name')->get(),
            'uniforms' => RequisitionUniform::query()->where('is_active', true)->orderBy('name')->get(),
            'contractTypes' => RequisitionContractType::query()->where('is_active', true)->orderBy('name')->get(),
            'recruiters' => RequisitionRecruiter::query()->where('is_active', true)->orderBy('name')->get(),
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

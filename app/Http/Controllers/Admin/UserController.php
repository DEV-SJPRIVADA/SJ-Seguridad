<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\SupplySite;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $query = User::query()
            ->with(['roles', 'permissions', 'creator'])
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $users = $query->paginate(14)->withQueryString();

        if ($users->isEmpty() && $users->total() > 0) {
            $users = $query->paginate(14, ['*'], 'page', 1)->withQueryString();
        }

        $selectedUserId = $request->integer('selected');
        $selectedUser = $selectedUserId > 0
            ? User::query()->with(['roles', 'permissions', 'creator'])->find($selectedUserId)
            : null;

        if (! $selectedUser && $users->isNotEmpty()) {
            /** @var \App\Models\User $selectedUser */
            $selectedUser = $users->first();
        }

        return view('admin.users.index', [
            'filters' => [
                'q' => $search,
            ],
            'permissionGroups' => $this->permissionGroups(),
            'selectedUser' => $selectedUser,
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'areas' => config('access.areas', []),
            'sites' => SupplySite::query()->active()->ordered()->get(),
            'allSites' => SupplySite::query()->ordered()->withCount(['users', 'supplyRequests'])->get(),
            'roles' => $this->roles(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'area_key' => $request->input('area_key'),
                'sede_id' => $request->input('sede_id'),
                'email' => Str::lower($request->string('email')->toString()),
                'password' => $request->string('password')->toString(),
                'is_active' => $request->boolean('is_active', true),
                'must_change_password' => $request->boolean('must_change_password', true),
                'created_by' => $request->user()->id,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($request->string('role')->toString());
            $user->syncPermissions($request->input('permissions', []));
        });

        return redirect()->route('admin.users.index')->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'areas' => config('access.areas', []),
            'sites' => SupplySite::query()->active()->ordered()->get(),
            'allSites' => SupplySite::query()->ordered()->withCount(['users', 'supplyRequests'])->get(),
            'permissionGroups' => $this->permissionGroups(),
            'roles' => $this->roles(),
            'selectedPermissions' => $user->permissions->pluck('name')->all(),
            'selectedRole' => old('role', $user->roles->pluck('name')->first()),
            'user' => $user->load(['roles', 'permissions']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $attributes = [
                'name' => $request->string('name')->toString(),
                'area_key' => $request->input('area_key'),
                'sede_id' => $request->input('sede_id'),
                'email' => Str::lower($request->string('email')->toString()),
                'is_active' => $request->boolean('is_active'),
                'must_change_password' => $request->boolean('must_change_password'),
            ];

            if ($request->filled('password')) {
                $attributes['password'] = $request->string('password')->toString();
                $attributes['must_change_password'] = false;
            }

            $user->update($attributes);
            $user->syncRoles([$request->string('role')->toString()]);
            $user->syncPermissions($request->input('permissions', []));
        });

        return redirect()->route('admin.users.edit', $user)->with('status', 'user-updated');
    }

    private function permissionGroups(): array
    {
        $allPermissions = Permission::query()->pluck('name')->all();

        // 1. Permisos Funcionales (Capacidades del sistema)
        $functional = collect(config('access.system_permissions'))
            ->filter(fn ($label, $name) => in_array($name, $allPermissions, true))
            ->map(fn ($label, $name) => [
                'name' => $name,
                'label' => $label,
                'category' => match (true) {
                    str_contains($name, 'supply') => 'Suministros',
                    str_contains($name, 'quality.documents') => 'Calidad',
                    str_contains($name, 'requisition') => 'Requisiciones',
                    default => 'Administración',
                },
            ])
            ->groupBy('category');

        // 2. Permisos de Área (Alcance)
        $areas = collect(config('access.areas'))
            ->map(function ($label, $key) use ($allPermissions) {
                return [
                    'key' => $key,
                    'label' => $label,
                    'options' => collect([
                        ['label' => 'Abrir Área', 'name' => "view.area.{$key}"],
                        ['label' => 'Gestionar Área', 'name' => "manage.area.{$key}"],
                        ['label' => 'Tablero Dashboard', 'name' => "view.board.{$key}.dashboard"],
                        ['label' => 'Tablero Requisiciones', 'name' => "view.board.{$key}.requisiciones"],
                        ['label' => 'Tablero Suministros', 'name' => "view.board.{$key}.suministros"],
                    ])->filter(fn ($opt) => in_array($opt['name'], $allPermissions, true))->values(),
                ];
            })
            ->values();

        return [
            'functional' => $functional,
            'areas' => $areas,
        ];
    }

    private function roles()
    {
        $orderedNames = ['super-admin', 'administrador', 'usuario'];

        return Role::query()
            ->whereIn('name', $orderedNames)
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $orderedNames, true))
            ->values();
    }
}

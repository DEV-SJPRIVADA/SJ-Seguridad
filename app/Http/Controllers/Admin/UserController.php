<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
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

        $selectedUser = null;
        $selectedId = $request->integer('selected');

        if ($selectedId > 0) {
            $selectedUser = User::query()
                ->with(['roles', 'permissions', 'creator'])
                ->find($selectedId);
        }

        if (! $selectedUser) {
            $selectedUser = $users->getCollection()->first()
                ?? User::query()->with(['roles', 'permissions', 'creator'])->orderBy('name')->first();
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
            'roles' => $this->roles(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::create([
                'name' => $request->string('name')->toString(),
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
        $permissions = Permission::query()->pluck('name')->all();

        $administrationPermissions = collect(config('access.system_permissions'))
            ->filter(fn (string $label, string $permission) => in_array($permission, $permissions, true))
            ->map(fn (string $label, string $permission) => [
                'label' => $label,
                'name' => $permission,
            ])
            ->values()
            ->all();

        $administrationModule = [
            'key' => 'administracion_usuarios',
            'area' => 'Administracion de usuarios',
            'rows' => collect($administrationPermissions)
                ->values()
                ->map(fn (array $permission, int $index) => [
                    'label' => ($index + 1).'.- '.$permission['label'],
                    'name' => $permission['name'],
                ])
                ->all(),
        ];

        $areaPermissions = collect(config('access.areas'))
            ->map(function (string $label, string $key) use ($permissions) {
                $rows = collect([
                    [
                        'label' => '1.- Abrir modulo (modo vista)',
                        'name' => "view.area.{$key}",
                    ],
                    [
                        'label' => '2.- Gestion del modulo',
                        'name' => "manage.area.{$key}",
                    ],
                ])->merge(
                    collect(config('access.boards', []))
                        ->map(fn (string $boardLabel, string $boardKey) => [
                            'label' => $boardLabel,
                            'name' => "view.board.{$key}.{$boardKey}",
                        ])
                        ->values()
                        ->map(fn (array $board, int $index) => [
                            'label' => ($index + 3).'.- Tablero '.$board['label'],
                            'name' => $board['name'],
                        ])
                )->filter(fn (array $row) => in_array($row['name'], $permissions, true))
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    'area' => $label,
                    'rows' => $rows,
                ];
            })
            ->values()
            ->all();

        return [
            'administration_module' => $administrationModule,
            'area_permissions' => $areaPermissions,
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

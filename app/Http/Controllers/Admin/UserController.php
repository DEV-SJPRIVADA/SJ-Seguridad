<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApplyUserAccessRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Mail\UserWelcomeMail;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\Admin\UserAccessProfileService;
use App\Services\Admin\UserAccessSummary;
use App\Services\Admin\UserManagementAuditService;
use App\Services\Admin\UserPermissionFormBuilder;
use App\Services\Admin\UserPermissionValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserPermissionFormBuilder $permissionFormBuilder,
        private readonly UserPermissionValidator $permissionValidator,
        private readonly UserAccessProfileService $accessProfileService,
        private readonly UserAccessSummary $accessSummary,
        private readonly UserManagementAuditService $userManagementAuditService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $includeInactive = $request->boolean('include_inactive');

        $query = User::query()
            ->with(['roles', 'permissions', 'creator'])
            ->when(! $includeInactive, fn ($builder) => $builder->where('is_active', true))
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
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
            /** @var User $selectedUser */
            $selectedUser = $users->first();
        }

        return view('admin.users.index', [
            'filters' => [
                'q' => $search,
                'include_inactive' => $includeInactive,
            ],
            'permissionForm' => $this->permissionFormBuilder->build(),
            'selectedUser' => $selectedUser,
            'accessSummary' => $selectedUser ? $this->accessSummary->summarize($selectedUser) : null,
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
            'users' => $users,
        ]);
    }

    public function create(Request $request): View
    {
        $copyContext = $this->resolveCopyContext($request);
        $copyProfile = $copyContext['profile'] ?? null;
        $copySource = $copyContext['source'] ?? null;

        return view('admin.users.create', [
            'areas' => config('access.areas', []),
            'sites' => $this->sitesForForm($copySource),
            'allSites' => SupplySite::query()->ordered()->withCount(['users', 'supplyRequests'])->get(),
            'roles' => $this->roles(),
            'permissionForm' => $this->permissionFormBuilder->build(),
            'copyCandidates' => $this->accessProfileService->copyCandidates(actor: $request->user()),
            'copyFromUser' => $copyContext['source'] ?? null,
            'copyDefaults' => $copyProfile,
            'copyError' => $request->filled('copy_from') && $copyContext === []
                ? 'No se pudo cargar el acceso del usuario seleccionado.'
                : null,
            'selectedPermissions' => old('permissions', $copyProfile['permissions'] ?? []),
            'selectedRole' => old('role', $copyProfile['role'] ?? null),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $permissions = $request->input('permissions', []);
        $areaKey = $request->input('area_key');
        $warnings = $this->permissionValidator->warnings($areaKey, $permissions);
        $documentNumber = $request->string('document_number')->toString();
        $temporaryPassword = $documentNumber;

        $role = $request->string('role')->toString();

        $user = DB::transaction(function () use ($request, $permissions, $documentNumber, $temporaryPassword, $role): User {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'document_number' => $documentNumber,
                'area_key' => $request->input('area_key'),
                'sede_id' => $request->input('sede_id'),
                'email' => Str::lower($request->string('email')->toString()),
                'password' => $temporaryPassword,
                'is_active' => $request->boolean('is_active', true),
                'must_change_password' => $request->boolean('must_change_password', true),
                'created_by' => $request->user()->id,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($role);
            $user->syncPermissions($permissions);

            return $user;
        });

        $this->userManagementAuditService->logUserCreated(
            user: $user,
            role: $role,
            permissionsCount: count($permissions),
        );

        Mail::to($user->email)->send(new UserWelcomeMail($user, $temporaryPassword));

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'user-created')
            ->with('permission_warnings', $warnings);
    }

    public function edit(Request $request, User $user): View
    {
        $user->load(['roles', 'permissions']);

        return view('admin.users.edit', [
            'areas' => config('access.areas', []),
            'sites' => $this->sitesForForm($user),
            'allSites' => SupplySite::query()->ordered()->withCount(['users', 'supplyRequests'])->get(),
            'permissionForm' => $this->permissionFormBuilder->build(),
            'roles' => $this->roles($user),
            'selectedPermissions' => $user->permissions->pluck('name')->all(),
            'selectedRole' => old('role', $user->roles->pluck('name')->first()),
            'user' => $user,
            'copyCandidates' => $this->accessProfileService->copyCandidates($user, $request->user()),
        ]);
    }

    public function applyAccess(ApplyUserAccessRequest $request, User $user): RedirectResponse
    {
        $source = User::query()
            ->with(['roles', 'permissions'])
            ->findOrFail($request->integer('source_user_id'));

        $includeArea = $request->boolean('include_area');
        $includeSede = $request->boolean('include_sede');

        $user->load(['roles', 'permissions']);
        $beforeRole = $this->userManagementAuditService->captureRole($user);
        $beforePermissions = $this->userManagementAuditService->captureDirectPermissions($user);

        DB::transaction(function () use ($request, $user, $source, $includeArea, $includeSede): void {
            $this->accessProfileService->applyToUser(
                target: $user,
                source: $source,
                actor: $request->user(),
                includeArea: $includeArea,
                includeSede: $includeSede,
            );
        });

        $user->refresh();
        $user->load(['roles', 'permissions']);

        $newRole = $this->userManagementAuditService->captureRole($user);
        $newPermissions = $this->userManagementAuditService->captureDirectPermissions($user);

        $this->userManagementAuditService->logAccessCopied(
            target: $user,
            source: $source,
            beforeRole: $beforeRole,
            beforePermissions: $beforePermissions,
            newRole: $newRole,
            newPermissions: $newPermissions,
            includeArea: $includeArea,
            includeSede: $includeSede,
        );

        $warnings = $this->permissionValidator->warnings($user->area_key, $newPermissions);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'access-applied')
            ->with('access_copy_source_name', $source->name)
            ->with('permission_warnings', $warnings);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $permissions = $request->input('permissions', []);
        $areaKey = $request->input('area_key');
        $warnings = $this->permissionValidator->warnings($areaKey, $permissions);
        $newRole = $request->string('role')->toString();
        $newPermissions = collect($permissions)->sort()->values()->all();
        $passwordReset = $request->filled('password');

        $user->load(['roles', 'permissions']);
        $beforeProfile = $this->userManagementAuditService->captureProfileState($user);
        $beforeIsActive = (bool) $user->is_active;
        $beforeRole = $this->userManagementAuditService->captureRole($user);
        $beforePermissions = $this->userManagementAuditService->captureDirectPermissions($user);

        DB::transaction(function () use ($request, $user, $permissions, $newRole, $passwordReset): void {
            $attributes = [
                'name' => $request->string('name')->toString(),
                'document_number' => $request->string('document_number')->toString(),
                'area_key' => $request->input('area_key'),
                'sede_id' => $request->input('sede_id'),
                'email' => Str::lower($request->string('email')->toString()),
                'is_active' => $request->boolean('is_active'),
                'must_change_password' => $request->boolean('must_change_password'),
            ];

            if ($passwordReset) {
                $attributes['password'] = $request->string('password')->toString();
                $attributes['must_change_password'] = false;
            }

            $user->update($attributes);
            $user->syncRoles([$newRole]);
            $user->syncPermissions($permissions);
        });

        $this->userManagementAuditService->logUserUpdated(
            user: $user,
            beforeProfile: $beforeProfile,
            beforeIsActive: $beforeIsActive,
            beforeRole: $beforeRole,
            beforePermissions: $beforePermissions,
            newRole: $newRole,
            newPermissions: $newPermissions,
            passwordReset: $passwordReset,
        );

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'user-updated')
            ->with('permission_warnings', $warnings);
    }

    /**
     * @return array{source?: User, profile?: array{role: string, area_key: ?string, sede_id: ?int, permissions: array<int, string>}}|array{}
     */
    private function resolveCopyContext(Request $request): array
    {
        $copyFromId = $request->integer('copy_from');

        if ($copyFromId <= 0) {
            return [];
        }

        $source = User::query()
            ->with(['roles', 'permissions'])
            ->find($copyFromId);

        if ($source === null || $request->user() === null) {
            return [];
        }

        if (! $this->accessProfileService->canCopyFrom($request->user(), $source)) {
            return [];
        }

        return [
            'source' => $source,
            'profile' => $this->accessProfileService->extractProfile(
                $source,
                $request->boolean('include_area', true),
                $request->boolean('include_sede', true),
            ),
        ];
    }

    private function roles(?User $forUser = null)
    {
        $orderedNames = ['super-admin', 'administrador', 'director', 'usuario'];
        $currentRole = $forUser?->roles->pluck('name')->first();

        if (is_string($currentRole) && $currentRole !== '' && ! in_array($currentRole, $orderedNames, true)) {
            $orderedNames[] = $currentRole;
        }

        return Role::query()
            ->whereIn('name', $orderedNames)
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $orderedNames, true))
            ->values();
    }

    /**
     * @return Collection<int, SupplySite>
     */
    private function sitesForForm(?User $user = null)
    {
        $sites = SupplySite::query()->active()->ordered()->get();

        if ($user?->sede_id && ! $sites->contains('id', $user->sede_id)) {
            $currentSite = SupplySite::query()->find($user->sede_id);

            if ($currentSite) {
                $sites->prepend($currentSite);
            }
        }

        return $sites;
    }
}

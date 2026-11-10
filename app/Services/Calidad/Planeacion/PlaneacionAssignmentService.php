<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorAssignee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PlaneacionAssignmentService
{
    /**
     * @var list<string>
     */
    public const CAPTURE_PERMISSIONS = [
        'view.board.calidad.planeacion_estrategica',
        'planeacion.captura.view',
        'planeacion.captura.edit',
    ];

    /**
     * @var list<string>
     */
    public const CONSULT_PERMISSIONS = [
        'planeacion.indicadores.view',
        'planeacion.dashboard.view',
    ];

    public const NEVER_REVOKE = 'planeacion.indicadores.edit';

    public function __construct(
        private readonly PlaneacionAuditLogService $audit,
    ) {}

    /**
     * Sincroniza asignados del indicador y otorga o revoca el mínimo de permisos.
     *
     * @param  list<array{user_id: int, can_consult?: bool}>  $assignees
     */
    public function sync(QualityPlanningIndicator $indicator, array $assignees, int $actorId): QualityPlanningIndicator
    {
        return DB::transaction(function () use ($indicator, $assignees, $actorId): QualityPlanningIndicator {
            $previous = $indicator->assignees()->get()->keyBy('user_id');
            $incomingIds = [];
            $affectedUserIds = $previous->keys()->map(fn ($id): int => (int) $id)->all();

            foreach ($assignees as $row) {
                $userId = (int) $row['user_id'];
                $incomingIds[] = $userId;
                $affectedUserIds[] = $userId;
                $canConsult = (bool) ($row['can_consult'] ?? false);

                QualityPlanningIndicatorAssignee::query()->updateOrCreate(
                    [
                        'indicator_id' => $indicator->id,
                        'user_id' => $userId,
                    ],
                    [
                        'can_capture' => true,
                        'can_consult' => $canConsult,
                    ],
                );

                $user = User::query()->findOrFail($userId);
                $this->grantMinimum($user, $canConsult);
            }

            $removedIds = $previous->keys()->diff($incomingIds);
            if ($removedIds->isNotEmpty()) {
                $indicator->assignees()->whereIn('user_id', $removedIds->all())->delete();
            }

            foreach (array_unique($affectedUserIds) as $userId) {
                $this->revokeUnused((int) $userId);
            }

            $this->audit->logModelChange(
                eventType: 'assignment',
                action: 'sync',
                model: $indicator,
                before: [
                    'assignees' => $previous->map(fn (QualityPlanningIndicatorAssignee $row): array => [
                        'user_id' => $row->user_id,
                        'can_consult' => $row->can_consult,
                    ])->values()->all(),
                ],
                after: [
                    'assignees' => collect($assignees)->map(fn (array $row): array => [
                        'user_id' => (int) $row['user_id'],
                        'can_consult' => (bool) ($row['can_consult'] ?? false),
                    ])->values()->all(),
                ],
                userId: $actorId,
            );

            return $indicator->fresh(['assignees.user']) ?? $indicator;
        });
    }

    public function grantMinimum(User $user, bool $canConsult): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->ensurePermissionsExist(self::CAPTURE_PERMISSIONS);
        $user->givePermissionTo(self::CAPTURE_PERMISSIONS);

        if ($canConsult) {
            $this->ensurePermissionsExist(self::CONSULT_PERMISSIONS);
            $user->givePermissionTo(self::CONSULT_PERMISSIONS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function revokeUnused(int $userId): void
    {
        $user = User::query()->find($userId);
        if (! $user instanceof User) {
            return;
        }

        if ($user->can(self::NEVER_REVOKE)) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $remaining = QualityPlanningIndicatorAssignee::query()
            ->where('user_id', $userId)
            ->get();

        if ($remaining->isEmpty()) {
            $user->revokePermissionTo(self::CAPTURE_PERMISSIONS);
            $user->revokePermissionTo(self::CONSULT_PERMISSIONS);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        if (! $remaining->contains(fn (QualityPlanningIndicatorAssignee $row): bool => $row->can_consult)) {
            $user->revokePermissionTo(self::CONSULT_PERMISSIONS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function ensurePermissionsExist(array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}

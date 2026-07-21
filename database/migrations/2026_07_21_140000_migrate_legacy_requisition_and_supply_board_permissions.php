<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const PERMISSION_REPLACEMENTS = [
        'manage.requisitions' => 'requisitions.tab.gestion',
        'view.board.gestion_humana.suministros' => 'view.board.compras.suministros',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSION_REPLACEMENTS as $legacy => $replacement) {
            Permission::findOrCreate($replacement, 'web');

            $legacyPermission = Permission::query()->where('name', $legacy)->first();

            if ($legacyPermission === null) {
                continue;
            }

            $replacementPermission = Permission::query()->where('name', $replacement)->firstOrFail();

            $userIds = DB::table('model_has_permissions')
                ->where('permission_id', $legacyPermission->id)
                ->where('model_type', 'App\Models\User')
                ->pluck('model_id');

            foreach ($userIds as $userId) {
                $hasReplacement = DB::table('model_has_permissions')
                    ->where('permission_id', $replacementPermission->id)
                    ->where('model_type', 'App\Models\User')
                    ->where('model_id', $userId)
                    ->exists();

                if (! $hasReplacement) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $replacementPermission->id,
                        'model_type' => 'App\Models\User',
                        'model_id' => $userId,
                    ]);
                }

                DB::table('model_has_permissions')
                    ->where('permission_id', $legacyPermission->id)
                    ->where('model_type', 'App\Models\User')
                    ->where('model_id', $userId)
                    ->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Migracion de datos legacy; no reversible de forma segura.
    }
};

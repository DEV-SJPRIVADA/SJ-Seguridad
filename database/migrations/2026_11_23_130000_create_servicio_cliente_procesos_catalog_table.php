<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FO-GI-22: «Proceso» pasa de texto libre a catálogo.
 *
 * - Crea `servicio_cliente_procesos` y la siembra con los procesos que ya existen
 *   en el sistema (catálogo MT-GI-11 de Calidad).
 * - Agrega `proceso_id` en `servicio_cliente_casos`.
 * - Elimina la columna de texto libre `proceso` (autorizado por el usuario).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('servicio_cliente_procesos')) {
            Schema::create('servicio_cliente_procesos', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });

            $this->seedFromSystemProcesses();
        }

        if (! Schema::hasColumn('servicio_cliente_casos', 'proceso_id')) {
            $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

            Schema::table('servicio_cliente_casos', function (Blueprint $table) use ($isSqlite): void {
                if ($isSqlite) {
                    // SQLite no permite agregar FKs a una tabla existente.
                    $table->unsignedBigInteger('proceso_id')->nullable();
                } else {
                    $table->foreignId('proceso_id')
                        ->nullable()
                        ->after('dias_habiles')
                        ->constrained('servicio_cliente_procesos')
                        ->nullOnDelete();
                }
            });
        }

        // Elimina la columna de texto libre (`proceso` o su nombre anterior `responsable_seguimiento`).
        foreach (['proceso', 'responsable_seguimiento'] as $legacyColumn) {
            if (Schema::hasColumn('servicio_cliente_casos', $legacyColumn)) {
                Schema::table('servicio_cliente_casos', function (Blueprint $table) use ($legacyColumn): void {
                    $table->dropColumn($legacyColumn);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('servicio_cliente_casos', 'proceso')) {
            Schema::table('servicio_cliente_casos', function (Blueprint $table): void {
                $table->text('proceso')->nullable();
            });
        }

        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if (Schema::hasColumn('servicio_cliente_casos', 'proceso_id')) {
            Schema::table('servicio_cliente_casos', function (Blueprint $table) use ($isSqlite): void {
                if (! $isSqlite) {
                    $table->dropForeign(['proceso_id']);
                }

                $table->dropColumn('proceso_id');
            });
        }

        Schema::dropIfExists('servicio_cliente_procesos');
    }

    /**
     * Toma los procesos del catálogo MT-GI-11 (Calidad). Si no existe, usa la base
     * canónica de procesos del sistema.
     */
    private function seedFromSystemProcesses(): void
    {
        $now = now();

        if (Schema::hasTable('calidad_mt_gi_11_procesos')) {
            $rows = DB::table('calidad_mt_gi_11_procesos')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['code', 'name', 'is_active', 'sort_order']);

            foreach ($rows as $row) {
                DB::table('servicio_cliente_procesos')->updateOrInsert(
                    ['code' => $row->code],
                    [
                        'name' => $row->name,
                        'is_active' => (bool) $row->is_active,
                        'sort_order' => (int) $row->sort_order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            if ($rows->isNotEmpty()) {
                return;
            }
        }

        foreach ([
            ['code' => 'GESTION_GERENCIAL', 'name' => 'GESTIÓN GERENCIAL', 'sort_order' => 1],
            ['code' => 'GESTION_INTEGRAL_SERVICIO_CLIENTE', 'name' => 'GESTIÓN INTEGRAL Y SERVICIO AL CLIENTE', 'sort_order' => 2],
            ['code' => 'GESTION_OPERATIVA', 'name' => 'GESTIÓN OPERATIVA', 'sort_order' => 3],
            ['code' => 'CENTRAL_MONITOREO', 'name' => 'CENTRAL DE MONITOREO', 'sort_order' => 4],
            ['code' => 'COMERCIAL_SERVICIOS_CONEXOS', 'name' => 'COMERCIAL Y SERVICIOS CONEXOS', 'sort_order' => 5],
            ['code' => 'GESTION_HUMANA', 'name' => 'GESTIÓN HUMANA', 'sort_order' => 6],
            ['code' => 'GESTION_ADMINISTRATIVA', 'name' => 'GESTIÓN ADMINISTRATIVA', 'sort_order' => 7],
            ['code' => 'GESTION_FINANCIERA', 'name' => 'GESTIÓN FINANCIERA', 'sort_order' => 8],
            ['code' => 'GESTION_DOCUMENTAL', 'name' => 'GESTIÓN DOCUMENTAL', 'sort_order' => 9],
            ['code' => 'GESTION_TIC', 'name' => 'GESTIÓN TIC', 'sort_order' => 10],
            ['code' => 'SST', 'name' => 'SEGURIDAD Y SALUD EN EL TRABAJO', 'sort_order' => 11],
            ['code' => 'GESTION_JURIDICA', 'name' => 'GESTIÓN JURÍDICA', 'sort_order' => 12],
            ['code' => 'GESTION_PLANEACION_PROGRAMACION', 'name' => 'GESTIÓN PLANEACIÓN Y PROGRAMACIÓN', 'sort_order' => 13],
        ] as $row) {
            DB::table('servicio_cliente_procesos')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
};

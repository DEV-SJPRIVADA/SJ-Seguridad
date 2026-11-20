<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogos del tablero MT-GI-11 (Calidad): procesos, tipos de documento,
 * tipos de solicitud, cargos del responsable y estados con color.
 *
 * Todos comparten la misma forma (code/name/is_active/sort_order) para que la
 * pestana Catalogos los administre con un solo servicio. `color` solo aplica a
 * estados y guarda la variante visual de `status-pill`.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'calidad_mt_gi_11_procesos',
            'calidad_mt_gi_11_tipos_documento',
            'calidad_mt_gi_11_tipos_solicitud',
            'calidad_mt_gi_11_cargos_responsable',
        ] as $table) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Estados: agrega `color` para pintar el pill sin hardcodear en la vista.
        Schema::create('calidad_mt_gi_11_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('color', 20)->default('muted');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        foreach ([
            ['code' => 'PENDIENTE', 'name' => 'PENDIENTE', 'color' => 'warning', 'sort_order' => 1],
            ['code' => 'EN_REVISION', 'name' => 'EN REVISIÓN', 'color' => 'info', 'sort_order' => 2],
            ['code' => 'APROBADA', 'name' => 'APROBADA', 'color' => 'success', 'sort_order' => 3],
            ['code' => 'RECHAZADA', 'name' => 'RECHAZADA', 'color' => 'danger', 'sort_order' => 4],
        ] as $row) {
            DB::table('calidad_mt_gi_11_estados')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'color' => $row['color'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        // Procesos: base tomada del catalogo documental de Calidad.
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
            DB::table('calidad_mt_gi_11_procesos')->updateOrInsert(
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

        // Tipos de documento: mismos del catalogo documental de Calidad.
        foreach ([
            ['code' => 'DOCUMENTO_GENERAL', 'name' => 'DOCUMENTO GENERAL', 'sort_order' => 1],
            ['code' => 'PROCEDIMIENTO', 'name' => 'PROCEDIMIENTO', 'sort_order' => 2],
            ['code' => 'FORMATO', 'name' => 'FORMATO', 'sort_order' => 3],
            ['code' => 'CARACTERIZACION', 'name' => 'CARACTERIZACIÓN', 'sort_order' => 4],
            ['code' => 'INSTRUCTIVO', 'name' => 'INSTRUCTIVO', 'sort_order' => 5],
            ['code' => 'PROGRAMA', 'name' => 'PROGRAMA', 'sort_order' => 6],
            ['code' => 'MANUAL', 'name' => 'MANUAL', 'sort_order' => 7],
            ['code' => 'REGLAMENTO', 'name' => 'REGLAMENTO', 'sort_order' => 8],
            ['code' => 'POLITICA', 'name' => 'POLÍTICA', 'sort_order' => 9],
            ['code' => 'INDICADOR_GESTION', 'name' => 'INDICADOR DE GESTIÓN', 'sort_order' => 10],
            ['code' => 'PROTOCOLO', 'name' => 'PROTOCOLO', 'sort_order' => 11],
            ['code' => 'PERFIL_CARGO', 'name' => 'PERFIL DE CARGO', 'sort_order' => 12],
            ['code' => 'FORMULARIO', 'name' => 'FORMULARIO', 'sort_order' => 13],
            ['code' => 'PLAN', 'name' => 'PLAN', 'sort_order' => 14],
        ] as $row) {
            DB::table('calidad_mt_gi_11_tipos_documento')->updateOrInsert(
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

        // Tipos de solicitud iniciales: el administrador los ajusta en Catalogos.
        foreach ([
            ['code' => 'CREACION', 'name' => 'CREACIÓN', 'sort_order' => 1],
            ['code' => 'ACTUALIZACION', 'name' => 'ACTUALIZACIÓN', 'sort_order' => 2],
            ['code' => 'ELIMINACION', 'name' => 'ELIMINACIÓN', 'sort_order' => 3],
            ['code' => 'ESTANDARIZACION', 'name' => 'ESTANDARIZACIÓN', 'sort_order' => 4],
        ] as $row) {
            DB::table('calidad_mt_gi_11_tipos_solicitud')->updateOrInsert(
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

    public function down(): void
    {
        Schema::dropIfExists('calidad_mt_gi_11_estados');
        Schema::dropIfExists('calidad_mt_gi_11_cargos_responsable');
        Schema::dropIfExists('calidad_mt_gi_11_tipos_solicitud');
        Schema::dropIfExists('calidad_mt_gi_11_tipos_documento');
        Schema::dropIfExists('calidad_mt_gi_11_procesos');
    }
};

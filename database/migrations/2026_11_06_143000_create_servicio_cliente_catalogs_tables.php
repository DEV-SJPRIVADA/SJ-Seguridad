<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'servicio_cliente_tipologias',
            'servicio_cliente_fuentes',
            'servicio_cliente_responsables',
            'servicio_cliente_categorias',
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

        $now = now();

        foreach ([
            ['code' => 'PETICION', 'name' => 'PETICIÓN', 'sort_order' => 1],
            ['code' => 'QUEJA', 'name' => 'QUEJA', 'sort_order' => 2],
            ['code' => 'RECLAMO', 'name' => 'RECLAMO', 'sort_order' => 3],
            ['code' => 'SUGERENCIA', 'name' => 'SUGERENCIA', 'sort_order' => 4],
        ] as $row) {
            DB::table('servicio_cliente_tipologias')->updateOrInsert(
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

        foreach ([
            ['code' => 'CORREO_ELECTRONICO', 'name' => 'CORREO ELECTRÓNICO', 'sort_order' => 1],
            ['code' => 'ENCUESTA', 'name' => 'ENCUESTA', 'sort_order' => 2],
            ['code' => 'PAGINA_WEB', 'name' => 'PÁGINA WEB', 'sort_order' => 3],
            ['code' => 'TELEFONICA', 'name' => 'TELEFÓNICA', 'sort_order' => 4],
            ['code' => 'WHATSAPP', 'name' => 'WHATSAPP', 'sort_order' => 5],
        ] as $row) {
            DB::table('servicio_cliente_fuentes')->updateOrInsert(
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
        Schema::dropIfExists('servicio_cliente_categorias');
        Schema::dropIfExists('servicio_cliente_responsables');
        Schema::dropIfExists('servicio_cliente_fuentes');
        Schema::dropIfExists('servicio_cliente_tipologias');
    }
};

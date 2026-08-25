<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('payroll_catalog_items')->insert([
            ['catalog_type' => 'firmas', 'code' => 'Gerente de Gestion Humana', 'name' => 'Maria Rodriguez', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'firmas', 'code' => 'Director de Operaciones', 'name' => 'Carlos Gomez', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'firmas', 'code' => 'Subdirector de Gestion Humana', 'name' => 'Ana Martinez', 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('payroll_catalog_items')->where('catalog_type', 'firmas')->delete();
    }
};

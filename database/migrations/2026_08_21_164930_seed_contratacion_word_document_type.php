<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('word_document_types')
            ->where('code', 'contratacion')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table('word_document_types')->insert([
            'code' => 'contratacion',
            'name' => 'Contratacion',
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('word_document_types')
            ->where('code', 'contratacion')
            ->delete();
    }
};

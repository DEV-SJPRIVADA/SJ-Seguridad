<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->string('referido', 255)->default('')->after('responsable_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->dropColumn('referido');
        });
    }
};

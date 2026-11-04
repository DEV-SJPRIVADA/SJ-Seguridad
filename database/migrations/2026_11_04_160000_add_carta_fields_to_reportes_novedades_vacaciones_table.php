<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes_novedades_vacaciones', function (Blueprint $table): void {
            $table->date('fecha_reintegro')->nullable()->after('fecha_fin');
            $table->string('periodos', 500)->nullable()->after('fecha_reintegro');
            $table->unsignedBigInteger('signatory_id')->nullable()->after('periodos');
        });
    }

    public function down(): void
    {
        Schema::table('reportes_novedades_vacaciones', function (Blueprint $table): void {
            $table->dropColumn(['fecha_reintegro', 'periodos', 'signatory_id']);
        });
    }
};

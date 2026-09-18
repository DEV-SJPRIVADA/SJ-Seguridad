<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_cursos', function (Blueprint $table) {
            $table->foreignId('curso_escuela_id')
                ->nullable()
                ->after('curso_tipo_id')
                ->constrained('curso_escuelas')
                ->nullOnDelete();
            $table->string('escuela_codigo', 30)->nullable()->after('curso_escuela_id');
            $table->string('escuela_nit', 30)->nullable()->after('escuela_codigo');
            $table->string('escuela_nombre', 255)->nullable()->after('escuela_nit');

            $table->index('escuela_codigo');
            $table->index('escuela_nit');
        });
    }

    public function down(): void
    {
        Schema::table('employee_cursos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('curso_escuela_id');
            $table->dropIndex(['escuela_codigo']);
            $table->dropIndex(['escuela_nit']);
            $table->dropColumn(['escuela_codigo', 'escuela_nit', 'escuela_nombre']);
        });
    }
};

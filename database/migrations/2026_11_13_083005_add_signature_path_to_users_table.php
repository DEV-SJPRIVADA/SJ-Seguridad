<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Firma manuscrita vigente del usuario (PNG privado). El PDF de la OC
     * no lee esta ruta: copia un snapshot al autorizar.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('signature_path')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('signature_path');
        });
    }
};

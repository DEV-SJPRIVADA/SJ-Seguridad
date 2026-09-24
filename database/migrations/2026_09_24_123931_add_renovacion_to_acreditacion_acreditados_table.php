<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acreditacion_acreditados', function (Blueprint $table): void {
            $table->string('renovacion', 30)->nullable()->after('estado');
            $table->index('renovacion');
        });
    }

    public function down(): void
    {
        Schema::table('acreditacion_acreditados', function (Blueprint $table): void {
            $table->dropIndex(['renovacion']);
            $table->dropColumn('renovacion');
        });
    }
};

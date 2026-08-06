<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_archive_consultations', function (Blueprint $table): void {
            $table->string('delivered_to', 150)->nullable()->after('documents_not_found');
        });
    }

    public function down(): void
    {
        Schema::table('employee_archive_consultations', function (Blueprint $table): void {
            $table->dropColumn('delivered_to');
        });
    }
};

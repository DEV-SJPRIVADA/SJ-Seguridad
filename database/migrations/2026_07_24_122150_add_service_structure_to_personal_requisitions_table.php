<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->text('service_structure')->nullable()->after('uniform_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->dropColumn('service_structure');
        });
    }
};

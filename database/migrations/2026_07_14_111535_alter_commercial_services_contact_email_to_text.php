<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_services', function (Blueprint $table) {
            $table->text('contact_email')->nullable()->change();
            $table->text('service_description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('commercial_services', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->change();
        });
    }
};

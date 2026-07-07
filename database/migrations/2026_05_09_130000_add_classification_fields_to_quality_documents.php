<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_documents', function (Blueprint $table) {
            $table->string('code')->nullable()->after('title');
            $table->string('root_process')->nullable()->after('code');
            $table->string('document_type')->nullable()->after('root_process');
        });
    }

    public function down(): void
    {
        Schema::table('quality_documents', function (Blueprint $table) {
            $table->dropColumn(['code', 'root_process', 'document_type']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('commercial_client_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('commercial_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('commercial_clients', function (Blueprint $table) {
            $table->id();
            $table->string('nit')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('legal_rep_name')->nullable();
            $table->string('legal_rep_doc')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('commercial_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_client_id')->constrained('commercial_clients')->cascadeOnDelete();
            $table->string('portfolio', 32);
            $table->string('contract_number')->nullable();
            $table->string('advisor_name')->nullable();
            $table->foreignId('commercial_sector_id')->nullable()->constrained('commercial_sectors')->nullOnDelete();
            $table->foreignId('commercial_client_type_id')->nullable()->constrained('commercial_client_types')->nullOnDelete();
            $table->foreignId('commercial_service_type_id')->nullable()->constrained('commercial_service_types')->nullOnDelete();
            $table->text('service_description')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->unsignedInteger('duration_months')->nullable();
            $table->string('doc_economic_proposal', 16)->nullable();
            $table->string('doc_fo_co_02', 16)->nullable();
            $table->string('doc_laft_or_queries', 16)->nullable();
            $table->string('doc_rut', 16)->nullable();
            $table->string('doc_financials', 16)->nullable();
            $table->string('doc_legal_rep_id', 16)->nullable();
            $table->string('doc_chamber', 16)->nullable();
            $table->string('doc_preinstall', 16)->nullable();
            $table->string('doc_contract', 16)->nullable();
            $table->string('doc_annex_2', 16)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['portfolio', 'contract_number']);
            $table->index('commercial_client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_services');
        Schema::dropIfExists('commercial_clients');
        Schema::dropIfExists('commercial_service_types');
        Schema::dropIfExists('commercial_client_types');
        Schema::dropIfExists('commercial_sectors');
    }
};

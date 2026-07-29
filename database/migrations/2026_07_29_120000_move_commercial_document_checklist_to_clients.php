<?php

use App\Services\Comercial\CommercialClientChecklistService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DOC_COLUMNS = [
        'doc_economic_proposal',
        'doc_fo_co_02',
        'doc_laft_or_queries',
        'doc_rut',
        'doc_financials',
        'doc_legal_rep_id',
        'doc_chamber',
        'doc_preinstall',
        'doc_contract',
        'doc_annex_2',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('commercial_clients', 'documentation_expires_on')) {
            Schema::table('commercial_clients', function (Blueprint $table): void {
                $table->date('documentation_expires_on')->nullable()->after('legal_rep_doc');
                $table->unsignedSmallInteger('alert_days_before')->nullable()->after('documentation_expires_on');
            });
        }

        if (! Schema::hasTable('commercial_client_document_items')) {
            Schema::create('commercial_client_document_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('commercial_client_id')->constrained('commercial_clients')->cascadeOnDelete();
                $table->string('document_key', 32);
                $table->string('status', 16)->nullable();
                $table->timestamps();

                $table->unique(['commercial_client_id', 'document_key'], 'cc_doc_items_client_doc_uq');
            });
        } else {
            Schema::table('commercial_client_document_items', function (Blueprint $table): void {
                $table->unique(['commercial_client_id', 'document_key'], 'cc_doc_items_client_doc_uq');
            });
        }

        app(CommercialClientChecklistService::class)->migrateFromLegacyServiceColumns();

        if (Schema::hasColumn('commercial_services', 'doc_economic_proposal')) {
            Schema::table('commercial_services', function (Blueprint $table): void {
                foreach (self::DOC_COLUMNS as $field) {
                    if (Schema::hasColumn('commercial_services', $field)) {
                        $table->dropColumn($field);
                    }
                    if (Schema::hasColumn('commercial_services', "{$field}_tracks_expiry")) {
                        $table->dropColumn("{$field}_tracks_expiry");
                    }
                    if (Schema::hasColumn('commercial_services', "{$field}_expires_on")) {
                        $table->dropColumn("{$field}_expires_on");
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('commercial_services', function (Blueprint $table): void {
            foreach (self::DOC_COLUMNS as $field) {
                $table->string($field, 16)->nullable();
                $table->boolean("{$field}_tracks_expiry")->default(false);
                $table->date("{$field}_expires_on")->nullable();
            }
        });

        Schema::dropIfExists('commercial_client_document_items');

        Schema::table('commercial_clients', function (Blueprint $table): void {
            $table->dropColumn(['documentation_expires_on', 'alert_days_before']);
        });
    }
};

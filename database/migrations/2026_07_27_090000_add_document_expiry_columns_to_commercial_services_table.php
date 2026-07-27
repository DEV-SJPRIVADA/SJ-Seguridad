<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const DOC_FIELDS = [
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
        Schema::table('commercial_services', function (Blueprint $table): void {
            foreach (self::DOC_FIELDS as $field) {
                $table->boolean("{$field}_tracks_expiry")->default(false)->after($field);
                $table->date("{$field}_expires_on")->nullable()->after("{$field}_tracks_expiry");
            }
        });
    }

    public function down(): void
    {
        Schema::table('commercial_services', function (Blueprint $table): void {
            $columns = [];
            foreach (self::DOC_FIELDS as $field) {
                $columns[] = "{$field}_tracks_expiry";
                $columns[] = "{$field}_expires_on";
            }
            $table->dropColumn($columns);
        });
    }
};

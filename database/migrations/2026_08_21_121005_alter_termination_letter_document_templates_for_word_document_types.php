<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('termination_letter_document_templates', 'word_document_type_id')) {
            Schema::table('termination_letter_document_templates', function (Blueprint $table): void {
                $table->unsignedBigInteger('word_document_type_id')->nullable()->after('id');
            });
        }

        Schema::table('termination_letter_document_templates', function (Blueprint $table): void {
            $table->foreign('word_document_type_id', 'term_letter_tpl_type_fk')
                ->references('id')
                ->on('word_document_types')
                ->restrictOnDelete();
        });

        $legacyRows = DB::table('termination_letter_document_templates')
            ->where(function ($query): void {
                $query->where('termination_cause_code', 'RENUNCIA')
                    ->orWhereNull('word_document_type_id');
            })
            ->get(['id', 'template_path']);

        foreach ($legacyRows as $row) {
            if (filled($row->template_path) && Storage::disk('local')->exists($row->template_path)) {
                Storage::disk('local')->delete($row->template_path);
            }
        }

        if ($legacyRows->isNotEmpty()) {
            DB::table('termination_letter_document_templates')
                ->whereIn('id', $legacyRows->pluck('id')->all())
                ->delete();
        }

        Schema::table('termination_letter_document_templates', function (Blueprint $table): void {
            $table->dropUnique('term_letter_tpl_cause_doc_uq');
            $table->dropIndex('term_letter_tpl_cause_idx');
            $table->dropColumn([
                'termination_cause_code',
                'document_key',
                'is_required',
            ]);
        });

        Schema::table('termination_letter_document_templates', function (Blueprint $table): void {
            $table->unsignedBigInteger('word_document_type_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Irreversible data cleanup: RENUNCIA template rows/files are not restored.
        Schema::table('termination_letter_document_templates', function (Blueprint $table): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['word_document_type_id']);
            } else {
                $table->dropForeign('term_letter_tpl_type_fk');
            }

            $table->dropColumn('word_document_type_id');
        });

        Schema::table('termination_letter_document_templates', function (Blueprint $table): void {
            $table->string('termination_cause_code', 50)->after('id');
            $table->string('document_key', 50)->after('termination_cause_code');
            $table->boolean('is_required')->default(true)->after('template_path');

            $table->unique(['termination_cause_code', 'document_key'], 'term_letter_tpl_cause_doc_uq');
            $table->index('termination_cause_code', 'term_letter_tpl_cause_idx');
        });
    }
};

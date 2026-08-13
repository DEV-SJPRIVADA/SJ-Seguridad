<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termination_letter_document_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('termination_cause_code', 50);
            $table->string('document_key', 50);
            $table->string('label', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('template_path', 255)->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['termination_cause_code', 'document_key'], 'term_letter_tpl_cause_doc_uq');
            $table->index('termination_cause_code', 'term_letter_tpl_cause_idx');
        });

        $now = now();

        foreach (config('employee_ficha.termination_letter_packs.RENUNCIA', []) as $document) {
            DB::table('termination_letter_document_templates')->insert([
                'termination_cause_code' => 'RENUNCIA',
                'document_key' => $document['key'],
                'label' => $document['label'],
                'sort_order' => $document['sort'],
                'template_path' => null,
                'is_required' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('termination_letter_document_templates');
    }
};

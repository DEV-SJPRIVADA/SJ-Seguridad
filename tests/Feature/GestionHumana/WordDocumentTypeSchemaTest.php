<?php

namespace Tests\Feature\GestionHumana;

use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WordDocumentTypeSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_document_types_table_and_desvinculacion_seed_exist(): void
    {
        $this->assertTrue(Schema::hasTable('word_document_types'));
        $this->assertTrue(Schema::hasColumns('word_document_types', [
            'id',
            'code',
            'name',
            'is_active',
            'sort_order',
            'created_at',
            'updated_at',
        ]));

        $type = WordDocumentType::query()->where('code', 'desvinculacion')->first();

        $this->assertNotNull($type);
        $this->assertSame('Desvinculacion', $type->name);
        $this->assertTrue($type->is_active);
        $this->assertSame(1, $type->sort_order);
    }

    public function test_templates_table_uses_type_fk_and_drops_legacy_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('termination_letter_document_templates', 'word_document_type_id'));
        $this->assertFalse(Schema::hasColumn('termination_letter_document_templates', 'termination_cause_code'));
        $this->assertFalse(Schema::hasColumn('termination_letter_document_templates', 'document_key'));
        $this->assertFalse(Schema::hasColumn('termination_letter_document_templates', 'is_required'));

        $this->assertSame(0, TerminationLetterDocumentTemplate::query()->count());
    }

    public function test_template_scopes_for_type_code_and_with_file(): void
    {
        $type = WordDocumentType::query()->where('code', 'desvinculacion')->firstOrFail();
        $other = WordDocumentType::query()->firstOrCreate(
            ['code' => 'contratacion'],
            [
                'name' => 'Contratacion',
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $withFile = TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => 'Carta con archivo',
            'sort_order' => 1,
            'template_path' => 'ficha-empleados/letter-templates/1/demo.docx',
        ]);

        TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => 'Carta sin archivo',
            'sort_order' => 2,
            'template_path' => null,
        ]);

        TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $other->id,
            'label' => 'Otra tipo',
            'sort_order' => 1,
            'template_path' => 'ficha-empleados/letter-templates/2/otro.docx',
        ]);

        $forType = TerminationLetterDocumentTemplate::query()
            ->forTypeCode('desvinculacion')
            ->ordered()
            ->get();

        $this->assertCount(2, $forType);
        $this->assertTrue($forType->every(
            static fn (TerminationLetterDocumentTemplate $template): bool => $template->word_document_type_id === $type->id
        ));

        $withFiles = TerminationLetterDocumentTemplate::query()
            ->forTypeCode('desvinculacion')
            ->withFile()
            ->get();

        $this->assertCount(1, $withFiles);
        $this->assertTrue($withFiles->first()->is($withFile));
        $this->assertTrue($withFile->type->is($type));
    }
}

<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\User;
use App\Models\WordDocumentType;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class PlantillasWordCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Storage::fake('local');
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_viewer_can_see_index_but_cannot_mutate(): void
    {
        $viewer = $this->viewerUser();
        $type = WordDocumentType::query()->where('code', 'desvinculacion')->firstOrFail();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.plantillas-word.index'))
            ->assertOk()
            ->assertSee('Desvinculacion', false);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.plantillas-word.types.store'), [
                'code' => 'contratacion',
                'name' => 'Contratacion',
                'is_active' => '1',
                'sort_order' => 2,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.plantillas-word.templates.store'), [
                'label' => 'Carta prueba',
                'word_document_type_id' => $type->id,
                'template' => $this->makeDocxFixture('[NOMBRE]'),
            ])
            ->assertForbidden();
    }

    public function test_manager_can_create_update_and_destroy_empty_type(): void
    {
        $manager = $this->managerUser();

        $this->actingAs($manager)
            ->post(route('gestion-humana.plantillas-word.types.store'), [
                'code' => 'nuevo_tipo',
                'name' => 'Nuevo Tipo',
                'is_active' => '1',
                'sort_order' => 10,
            ])
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'tipos']));

        $type = WordDocumentType::query()->where('code', 'nuevo_tipo')->firstOrFail();
        $this->assertSame('Nuevo Tipo', $type->name);
        $this->assertTrue($type->is_active);

        $this->actingAs($manager)
            ->patch(route('gestion-humana.plantillas-word.types.update', $type), [
                'code' => 'nuevo_tipo',
                'name' => 'Nuevo Tipo Editado',
                'is_active' => '0',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'tipos']));

        $type->refresh();
        $this->assertSame('Nuevo Tipo Editado', $type->name);
        $this->assertFalse($type->is_active);
        $this->assertSame(5, $type->sort_order);

        $this->actingAs($manager)
            ->delete(route('gestion-humana.plantillas-word.types.destroy', $type))
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'tipos']));

        $this->assertDatabaseMissing('word_document_types', ['id' => $type->id]);
    }

    public function test_cannot_destroy_type_with_templates(): void
    {
        $manager = $this->managerUser();
        $type = WordDocumentType::query()->where('code', 'desvinculacion')->firstOrFail();

        TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => 'Carta bloqueo',
            'sort_order' => 1,
            'template_path' => 'ficha-empleados/letter-templates/'.$type->id.'/demo.docx',
        ]);

        $this->actingAs($manager)
            ->delete(route('gestion-humana.plantillas-word.types.destroy', $type))
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'tipos']))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('word_document_types', ['id' => $type->id, 'code' => 'desvinculacion']);
    }

    public function test_manager_can_store_replace_download_and_destroy_template(): void
    {
        $manager = $this->managerUser();
        $type = WordDocumentType::query()->where('code', 'desvinculacion')->firstOrFail();

        $this->actingAs($manager)
            ->post(route('gestion-humana.plantillas-word.templates.store'), [
                'label' => 'Aceptacion renuncia',
                'word_document_type_id' => $type->id,
                'sort_order' => 1,
                'template' => $this->makeDocxFixture('[NOMBRE]'),
            ])
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'plantillas']));

        $template = TerminationLetterDocumentTemplate::query()->where('label', 'Aceptacion renuncia')->firstOrFail();
        $this->assertSame($type->id, $template->word_document_type_id);
        $this->assertNotNull($template->template_path);
        $this->assertStringContainsString('ficha-empleados/letter-templates/'.$type->id.'/', (string) $template->template_path);
        Storage::disk('local')->assertExists((string) $template->template_path);
        $originalPath = $template->template_path;

        $storeLog = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'termination_letter_template')
            ->where('action', 'store')
            ->where('auditable_id', $template->id)
            ->firstOrFail();
        $this->assertSame($manager->id, $storeLog->user_id);
        $this->assertSame('Aceptacion renuncia', $storeLog->metadata['label']);

        $this->actingAs($manager)
            ->get(route('gestion-humana.plantillas-word.templates.download', $template))
            ->assertOk();

        $this->actingAs($manager)
            ->post(route('gestion-humana.plantillas-word.templates.replace', $template), [
                'template' => $this->makeDocxFixture('[NOMBRE] [CEDULA]'),
            ])
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'plantillas']));

        $template->refresh();
        $this->assertSame('Aceptacion renuncia', $template->label);
        $this->assertSame($type->id, $template->word_document_type_id);
        $this->assertNotSame($originalPath, $template->template_path);
        Storage::disk('local')->assertExists((string) $template->template_path);
        Storage::disk('local')->assertMissing((string) $originalPath);

        $this->assertTrue(
            AuditLog::query()
                ->where('module', 'ficha_empleados')
                ->where('event_type', 'termination_letter_template')
                ->where('action', 'replace')
                ->where('auditable_id', $template->id)
                ->exists()
        );

        $this->actingAs($manager)
            ->delete(route('gestion-humana.plantillas-word.templates.destroy', $template))
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'plantillas']));

        $this->assertDatabaseMissing('termination_letter_document_templates', ['id' => $template->id]);
        Storage::disk('local')->assertMissing((string) $template->template_path);

        $this->assertTrue(
            AuditLog::query()
                ->where('module', 'ficha_empleados')
                ->where('event_type', 'termination_letter_template')
                ->where('action', 'delete')
                ->where('metadata->template_id', $template->id)
                ->exists()
        );
    }

    public function test_type_store_writes_audit_event(): void
    {
        $manager = $this->managerUser();

        $this->actingAs($manager)
            ->post(route('gestion-humana.plantillas-word.types.store'), [
                'code' => 'otro_tipo',
                'name' => 'Otro tipo',
                'is_active' => '1',
                'sort_order' => 3,
            ])
            ->assertRedirect(route('gestion-humana.plantillas-word.index', ['tab' => 'tipos']));

        $type = WordDocumentType::query()->where('code', 'otro_tipo')->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'word_document_type')
            ->where('action', 'store')
            ->where('auditable_id', $type->id)
            ->firstOrFail();

        $this->assertSame('otro_tipo', $log->metadata['code']);
        $this->assertSame($manager->id, $log->user_id);
    }

    public function test_cannot_store_template_for_inactive_type(): void
    {
        $manager = $this->managerUser();
        $type = WordDocumentType::query()->create([
            'code' => 'archivado',
            'name' => 'Archivado',
            'is_active' => false,
            'sort_order' => 9,
        ]);

        $this->actingAs($manager)
            ->post(route('gestion-humana.plantillas-word.templates.store'), [
                'label' => 'No debe guardar',
                'word_document_type_id' => $type->id,
                'template' => $this->makeDocxFixture('[NOMBRE]'),
            ])
            ->assertSessionHasErrors('word_document_type_id');

        $this->assertSame(0, TerminationLetterDocumentTemplate::query()->count());
    }

    public function test_index_lists_template_type_column(): void
    {
        $manager = $this->managerUser();
        $type = WordDocumentType::query()->where('code', 'desvinculacion')->firstOrFail();

        TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => 'Carta listado',
            'sort_order' => 1,
            'template_path' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.plantillas-word.index', ['tab' => 'plantillas']))
            ->assertOk()
            ->assertSee('Carta listado', false)
            ->assertSee('Desvinculacion', false)
            ->assertDontSee('Agregar tipo', false);
    }

    public function test_index_tabs_switch_between_tipos_and_plantillas(): void
    {
        $manager = $this->managerUser();

        $this->actingAs($manager)
            ->get(route('gestion-humana.plantillas-word.index', ['tab' => 'tipos']))
            ->assertOk()
            ->assertSee('Tipos de documento', false)
            ->assertSee('Agregar tipo', false)
            ->assertDontSee('Agregar plantilla', false);

        $this->actingAs($manager)
            ->get(route('gestion-humana.plantillas-word.index', ['tab' => 'plantillas']))
            ->assertOk()
            ->assertSee('Agregar plantilla', false)
            ->assertDontSee('Agregar tipo', false);
    }

    private function managerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.plantillas_word',
            'plantillas_word.view',
            'plantillas_word.manage',
        ]);

        return $user;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.plantillas_word',
            'plantillas_word.view',
        ]);

        return $user;
    }

    private function makeDocxFixture(string $content): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'pw-fixture-');
        $path = $temp.'.docx';
        @unlink($temp);

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText($content);
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        return new UploadedFile(
            $path,
            'plantilla.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
    }
}

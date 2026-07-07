<?php

namespace Tests\Feature;

use App\Models\QualityDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QualityDocumentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        Storage::fake('local');
    }

    public function test_quality_manager_can_create_file_document_and_assign_areas(): void
    {
        $manager = $this->qualityManager();

        $response = $this->actingAs($manager)->post(route('quality-documents.admin.store', ['module' => 'calidad']), [
            'title' => 'Procedimiento SG-SST',
            'code' => 'SG-PR-001',
            'root_process' => 'calidad',
            'document_type' => 'formato',
            'description' => 'Documento base de calidad.',
            'type' => QualityDocument::TYPE_FILE,
            'file' => UploadedFile::fake()->create('procedimiento.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'areas' => ['operaciones', 'calidad'],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('quality-documents.admin.index', ['module' => 'calidad']));
        $this->assertDatabaseHas('quality_documents', [
            'title' => 'Procedimiento SG-SST',
            'type' => QualityDocument::TYPE_FILE,
            'is_active' => true,
            'uploaded_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('quality_document_areas', [
            'area_key' => 'operaciones',
        ]);
    }

    public function test_quality_manager_can_toggle_document_status(): void
    {
        $manager = $this->qualityManager();
        $document = $this->createDocument($manager, ['operaciones'], true);

        $response = $this->actingAs($manager)->patch(route('quality-documents.admin.toggle', [
            'module' => 'calidad',
            'qualityDocument' => $document->id,
        ]));

        $response->assertRedirect(route('quality-documents.admin.index', ['module' => 'calidad']));
        $this->assertDatabaseHas('quality_documents', [
            'id' => $document->id,
            'is_active' => false,
        ]);
    }

    public function test_operations_user_sees_assigned_active_document_in_library(): void
    {
        $manager = $this->qualityManager();
        $viewer = $this->areaUser('operaciones');
        $document = $this->createDocument($manager, ['operaciones'], true);

        $response = $this->actingAs($viewer)->get(route('quality-documents.library.index', ['module' => 'operaciones']));

        $response->assertOk();
        $response->assertSee($document->title);
    }

    public function test_operations_user_does_not_see_inactive_or_unassigned_document(): void
    {
        $manager = $this->qualityManager();
        $viewer = $this->areaUser('operaciones');
        $inactive = $this->createDocument($manager, ['operaciones'], false, 'Doc inactivo');
        $unassigned = $this->createDocument($manager, ['comercial'], true, 'Doc comercial');

        $response = $this->actingAs($viewer)->get(route('quality-documents.library.index', ['module' => 'operaciones']));

        $response->assertOk();
        $response->assertDontSee($inactive->title);
        $response->assertDontSee($unassigned->title);
    }

    public function test_user_without_manage_permission_cannot_access_admin_routes(): void
    {
        $viewer = $this->areaUser('operaciones');

        $response = $this->actingAs($viewer)->get(route('quality-documents.admin.index', ['module' => 'operaciones']));

        $response->assertForbidden();
    }

    public function test_download_requires_assignment_to_module(): void
    {
        $manager = $this->qualityManager();
        $viewer = $this->areaUser('operaciones');
        $document = $this->createFileDocument($manager, ['comercial'], true);

        $response = $this->actingAs($viewer)->get(route('quality-documents.library.download', [
            'module' => 'operaciones',
            'qualityDocument' => $document->id,
        ]));

        $response->assertForbidden();
    }

    public function test_assigned_user_can_download_file(): void
    {
        $manager = $this->qualityManager();
        $viewer = $this->areaUser('operaciones');
        $document = $this->createFileDocument($manager, ['operaciones'], true);

        $response = $this->actingAs($viewer)->get(route('quality-documents.library.download', [
            'module' => 'operaciones',
            'qualityDocument' => $document->id,
        ]));

        $response->assertOk();
    }

    public function test_external_link_redirects_for_authorized_user(): void
    {
        $manager = $this->qualityManager();
        $viewer = $this->areaUser('operaciones');
        $document = QualityDocument::create([
            'title' => 'Norma externa',
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/norma',
            'is_active' => true,
            'uploaded_by' => $manager->id,
        ]);
        $document->areas()->create(['area_key' => 'operaciones']);

        $response = $this->actingAs($viewer)->get(route('quality-documents.library.open', [
            'module' => 'operaciones',
            'qualityDocument' => $document->id,
        ]));

        $response->assertRedirect('https://example.com/norma');
    }

    public function test_documents_board_visible_for_any_area_with_access(): void
    {
        $viewer = $this->areaUser('operaciones');

        $this->assertTrue($viewer->canViewDocumentsBoardFor('operaciones'));
    }

    public function test_documents_board_hidden_without_area_access(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');

        $this->assertFalse($user->canViewDocumentsBoardFor('operaciones'));
    }

    public function test_area_manager_without_document_permission_cannot_access_admin(): void
    {
        $manager = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('manage.area.calidad');
        $manager->givePermissionTo('view.area.calidad');

        $this->assertFalse($manager->can('manage.quality.documents'));

        $this->actingAs($manager)
            ->get(route('quality-documents.admin.index', ['module' => 'calidad']))
            ->assertForbidden();
    }

    public function test_quality_manager_sees_admin_tab_only_in_calidad_module(): void
    {
        $manager = $this->qualityManager();

        $this->assertTrue($manager->qualityDocumentBoardTabsFor('calidad')->contains('administrar'));
        $this->assertFalse($manager->qualityDocumentBoardTabsFor('operaciones')->contains('administrar'));
    }

    public function test_manager_can_assign_document_to_specific_user(): void
    {
        $manager = $this->qualityManager();
        $recipient = $this->areaUser('operaciones');

        $response = $this->actingAs($manager)->post(route('quality-documents.admin.store', ['module' => 'calidad']), [
            'title' => 'Documento confidencial',
            'code' => 'SG-CF-001',
            'root_process' => 'calidad',
            'document_type' => 'instructivo',
            'description' => 'Solo para un usuario.',
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/confidencial',
            'users' => [$recipient->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('quality-documents.admin.index', ['module' => 'calidad']));
        $this->assertDatabaseHas('quality_document_users', [
            'user_id' => $recipient->id,
        ]);
    }

    public function test_recipient_sees_document_in_my_documents_and_can_download(): void
    {
        $manager = $this->qualityManager();
        $recipient = $this->areaUser('operaciones');
        $document = $this->createFileDocumentForUsers($manager, [$recipient->id], true);

        $this->actingAs($recipient)
            ->get(route('quality-documents.mine.index', ['module' => 'operaciones']))
            ->assertOk()
            ->assertSee($document->title);

        $this->actingAs($recipient)
            ->get(route('quality-documents.mine.download', ['module' => 'operaciones', 'qualityDocument' => $document->id]))
            ->assertOk();
    }

    public function test_user_only_document_not_visible_in_area_library(): void
    {
        $manager = $this->qualityManager();
        $recipient = $this->areaUser('operaciones');
        $colleague = $this->areaUser('operaciones');
        $document = $this->createDocumentForUsers($manager, [$recipient->id], true, 'Solo para destinatario');

        $this->actingAs($colleague)
            ->get(route('quality-documents.library.index', ['module' => 'operaciones']))
            ->assertOk()
            ->assertDontSee($document->title);

        $this->actingAs($colleague)
            ->get(route('quality-documents.library.download', [
                'module' => 'operaciones',
                'qualityDocument' => $document->id,
            ]))
            ->assertForbidden();
    }

    public function test_has_active_for_user_controls_personal_documents_visibility(): void
    {
        $manager = $this->qualityManager();
        $recipient = $this->areaUser('operaciones');

        $this->assertFalse(QualityDocument::hasActiveForUser($recipient->id));

        $this->createDocumentForUsers($manager, [$recipient->id], true);

        $this->assertTrue(QualityDocument::hasActiveForUser($recipient->id));
    }

    public function test_recipient_sees_mis_documentos_tab_in_documents_board(): void
    {
        $manager = $this->qualityManager();
        $recipient = $this->areaUser('operaciones');
        $this->createDocumentForUsers($manager, [$recipient->id], true);

        $tabs = $recipient->qualityDocumentBoardTabsFor('operaciones');

        $this->assertTrue($tabs->contains('biblioteca'));
        $this->assertTrue($tabs->contains('mis_documentos'));
    }

    public function test_store_requires_at_least_one_area_or_user(): void
    {
        $manager = $this->qualityManager();

        $response = $this->actingAs($manager)->post(route('quality-documents.admin.store', ['module' => 'calidad']), [
            'title' => 'Sin destino',
            'code' => 'SG-ND-001',
            'root_process' => 'calidad',
            'document_type' => 'formato',
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/sin-destino',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('areas');
    }

    private function qualityManager(): User
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('manage.quality.documents');
        $user->givePermissionTo('view.area.calidad');

        return $user;
    }

    private function areaUser(string $areaKey): User
    {
        $user = User::factory()->create([
            'area_key' => $areaKey,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo("view.area.{$areaKey}");

        return $user;
    }

    /**
     * @param  array<int, string>  $areas
     */
    private function createDocument(User $manager, array $areas, bool $active, string $title = 'Documento de prueba'): QualityDocument
    {
        $document = QualityDocument::create([
            'title' => $title,
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/doc',
            'is_active' => $active,
            'uploaded_by' => $manager->id,
        ]);

        foreach ($areas as $areaKey) {
            $document->areas()->create(['area_key' => $areaKey]);
        }

        return $document;
    }

    /**
     * @param  array<int, string>  $areas
     */
    private function createFileDocument(User $manager, array $areas, bool $active): QualityDocument
    {
        $path = 'quality-documents/test.docx';
        Storage::disk('local')->put($path, 'contenido de prueba');

        $document = QualityDocument::create([
            'title' => 'Archivo de prueba',
            'type' => QualityDocument::TYPE_FILE,
            'file_path' => $path,
            'original_name' => 'test.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 100,
            'is_active' => $active,
            'uploaded_by' => $manager->id,
        ]);

        foreach ($areas as $areaKey) {
            $document->areas()->create(['area_key' => $areaKey]);
        }

        return $document;
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function createDocumentForUsers(User $manager, array $userIds, bool $active, string $title = 'Documento personal'): QualityDocument
    {
        $document = QualityDocument::create([
            'title' => $title,
            'code' => 'SG-US-001',
            'root_process' => 'calidad',
            'document_type' => 'formato',
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/personal',
            'is_active' => $active,
            'uploaded_by' => $manager->id,
        ]);

        foreach ($userIds as $userId) {
            $document->assignedUsers()->create(['user_id' => $userId]);
        }

        return $document;
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function createFileDocumentForUsers(User $manager, array $userIds, bool $active): QualityDocument
    {
        $path = 'quality-documents/personal.docx';
        Storage::disk('local')->put($path, 'contenido personal');

        $document = QualityDocument::create([
            'title' => 'Archivo personal',
            'code' => 'SG-US-002',
            'root_process' => 'calidad',
            'document_type' => 'formato',
            'type' => QualityDocument::TYPE_FILE,
            'file_path' => $path,
            'original_name' => 'personal.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 100,
            'is_active' => $active,
            'uploaded_by' => $manager->id,
        ]);

        foreach ($userIds as $userId) {
            $document->assignedUsers()->create(['user_id' => $userId]);
        }

        return $document;
    }
}

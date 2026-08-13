<?php

namespace Tests\Feature\QualityDocuments;

use App\Models\AuditLog;
use App\Models\QualityDocument;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QualityDocumentAuditTest extends TestCase
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

    public function test_store_writes_create_audit_event(): void
    {
        $manager = $this->qualityManager();

        $this->actingAs($manager)->post(route('quality-documents.admin.store', ['module' => 'calidad']), [
            'title' => 'Procedimiento Audit',
            'code' => 'SG-AU-001',
            'process_key' => 'gestion_documental',
            'document_type' => 'procedimiento',
            'origin' => 'interno',
            'document_status' => 'aprobado',
            'activity_status' => 'actualizada',
            'storage_type' => 'digital',
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/audit',
            'is_active' => '1',
            'areas' => ['operaciones', 'calidad'],
        ])->assertRedirect();

        $document = QualityDocument::query()->where('code', 'SG-AU-001')->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'quality_documents')
            ->where('event_type', 'document')
            ->where('action', 'create')
            ->where('auditable_id', $document->id)
            ->firstOrFail();

        $this->assertSame('calidad', $log->area);
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame([
            'code' => 'SG-AU-001',
            'title' => 'Procedimiento Audit',
            'type' => QualityDocument::TYPE_LINK,
        ], $log->new_values);
        $this->assertSame(2, $log->metadata['areas_count']);
        $this->assertSame(0, $log->metadata['users_count']);
    }

    public function test_update_writes_update_audit_event(): void
    {
        $manager = $this->qualityManager();
        $document = $this->createDocument($manager, ['calidad'], true, 'Titulo Anterior');

        $this->actingAs($manager)->patch(route('quality-documents.admin.update', [
            'module' => 'calidad',
            'qualityDocument' => $document->id,
        ]), [
            'title' => 'Titulo Actualizado',
            'code' => 'SG-AU-002',
            'process_key' => 'gestion_documental',
            'document_type' => 'formato',
            'origin' => 'interno',
            'document_status' => 'aprobado',
            'activity_status' => 'actualizada',
            'storage_type' => 'digital',
            'current_version' => '02',
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/actualizado',
            'is_active' => '1',
            'areas' => ['calidad'],
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'quality_documents')
            ->where('event_type', 'document')
            ->where('action', 'update')
            ->where('auditable_id', $document->id)
            ->firstOrFail();

        $this->assertSame('SG-MD-001', $log->old_values['code']);
        $this->assertSame('SG-AU-002', $log->new_values['code']);
        $this->assertSame('Titulo Anterior', $log->old_values['title']);
        $this->assertSame('Titulo Actualizado', $log->new_values['title']);
        $this->assertSame('02', $log->new_values['current_version']);
    }

    public function test_toggle_status_writes_deactivate_audit_event(): void
    {
        $manager = $this->qualityManager();
        $document = $this->createDocument($manager, ['calidad'], true);

        $this->actingAs($manager)->patch(route('quality-documents.admin.toggle', [
            'module' => 'calidad',
            'qualityDocument' => $document->id,
        ]))->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'quality_documents')
            ->where('event_type', 'document')
            ->where('action', 'deactivate')
            ->where('auditable_id', $document->id)
            ->firstOrFail();

        $this->assertSame('calidad', $log->area);
        $this->assertSame($document->code, $log->metadata['code']);
    }

    public function test_destroy_writes_delete_audit_event(): void
    {
        $manager = $this->qualityManager();
        $document = $this->createDocument($manager, ['calidad'], true, 'Documento a eliminar');

        $this->actingAs($manager)->delete(route('quality-documents.admin.destroy', [
            'module' => 'calidad',
            'qualityDocument' => $document->id,
        ]))->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'quality_documents')
            ->where('event_type', 'document')
            ->where('action', 'delete')
            ->where('auditable_id', $document->id)
            ->firstOrFail();

        $this->assertSame($document->code, $log->metadata['code']);
        $this->assertSame('Documento a eliminar', $log->metadata['title']);
    }

    public function test_admin_export_writes_admin_excel_audit_event(): void
    {
        $manager = $this->qualityManager();
        $this->createDocument($manager, ['calidad'], true, 'Export Audit Doc');

        $this->actingAs($manager)->get(route('quality-documents.admin.export', [
            'module' => 'calidad',
        ]))->assertOk();

        $log = AuditLog::query()
            ->where('module', 'quality_documents')
            ->where('event_type', 'export')
            ->where('action', 'admin_excel')
            ->firstOrFail();

        $this->assertSame('calidad', $log->area);
        $this->assertSame(1, $log->metadata['row_count']);
    }

    public function test_audit_disabled_skips_quality_document_writes(): void
    {
        Config::set('audit.enabled', false);

        $manager = $this->qualityManager();

        $this->actingAs($manager)->post(route('quality-documents.admin.store', ['module' => 'calidad']), [
            'title' => 'Sin Audit',
            'code' => 'SG-NA-001',
            'process_key' => 'gestion_documental',
            'document_type' => 'formato',
            'origin' => 'interno',
            'document_status' => 'aprobado',
            'activity_status' => 'actualizada',
            'storage_type' => 'digital',
            'type' => QualityDocument::TYPE_LINK,
            'external_url' => 'https://example.com/sin-audit',
            'is_active' => '1',
            'areas' => ['calidad'],
        ])->assertRedirect();

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'quality_documents',
            'event_type' => 'document',
            'action' => 'create',
        ]);
    }

    private function qualityManager(): User
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('manage.quality.documents');

        return $user;
    }

    /**
     * @param  array<int, string>  $areas
     */
    private function createDocument(User $manager, array $areas, bool $active, string $title = 'Documento de prueba'): QualityDocument
    {
        $document = QualityDocument::create([
            'code' => 'SG-MD-001',
            'process_key' => 'gestion_documental',
            'document_type' => 'formato',
            'origin' => 'interno',
            'document_status' => 'aprobado',
            'activity_status' => 'actualizada',
            'storage_type' => 'digital',
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
}

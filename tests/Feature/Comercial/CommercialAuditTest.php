<?php

namespace Tests\Feature\Comercial;

use App\Models\AuditLog;
use App\Models\CommercialClient;
use App\Models\CommercialSector;
use App\Models\CommercialService;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CommercialAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_client_store_writes_create_audit_event(): void
    {
        $manager = $this->matrizManager();

        $this->actingAs($manager)->post(route('comercial.matriz.clients.store'), [
            'nit' => '900888001-1',
            'name' => 'Cliente Audit SA',
            'city' => 'Cali',
            'legal_rep_name' => 'Representante Legal',
        ])->assertRedirect();

        $client = CommercialClient::query()->where('nit', '900888001-1')->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'client')
            ->where('action', 'create')
            ->where('auditable_id', $client->id)
            ->firstOrFail();

        $this->assertSame('comercial', $log->area);
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame([
            'nit' => '900888001-1',
            'name' => 'Cliente Audit SA',
            'city' => 'Cali',
        ], $log->new_values);
        $this->assertSame('Representante Legal', $log->metadata['legal_rep_name']);
    }

    public function test_client_update_writes_update_audit_event(): void
    {
        $manager = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900888002-1',
            'name' => 'Nombre Anterior',
            'city' => 'Bogota',
            'phone' => '3001111111',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $this->actingAs($manager)->patch(route('comercial.matriz.clients.update', $client), [
            'nit' => '900888002-1',
            'name' => 'Nombre Actualizado',
            'city' => 'Medellin',
            'phone' => '3002222222',
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'client')
            ->where('action', 'update')
            ->where('auditable_id', $client->id)
            ->firstOrFail();

        $this->assertSame([
            'name' => 'Nombre Anterior',
            'city' => 'Bogota',
            'phone' => '3001111111',
        ], $log->old_values);
        $this->assertSame([
            'name' => 'Nombre Actualizado',
            'city' => 'Medellin',
            'phone' => '3002222222',
        ], $log->new_values);
    }

    public function test_service_store_writes_create_audit_event(): void
    {
        $manager = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900888003-1',
            'name' => 'Cliente Servicio Audit',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $this->actingAs($manager)->post(route('comercial.matriz.services.store'), [
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'CT-AUD-001',
        ])->assertRedirect();

        $service = CommercialService::query()->where('contract_number', 'CT-AUD-001')->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'service')
            ->where('action', 'create')
            ->where('auditable_id', $service->id)
            ->firstOrFail();

        $this->assertSame($client->id, $log->metadata['commercial_client_id']);
        $this->assertSame('CT-AUD-001', $log->metadata['contract_number']);
        $this->assertSame(CommercialService::PORTFOLIO_SEG_FISICA, $log->metadata['portfolio']);
    }

    public function test_service_deactivate_and_activate_write_audit_events(): void
    {
        $manager = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900888004-1',
            'name' => 'Cliente Activa Audit',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $service = $client->services()->create([
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'CT-AUD-ACT',
            'is_active' => true,
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('comercial.matriz.services.inactivate', $service))
            ->assertRedirect();

        $deactivateLog = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'service')
            ->where('action', 'deactivate')
            ->where('auditable_id', $service->id)
            ->firstOrFail();

        $this->assertTrue($deactivateLog->metadata['previous_is_active']);

        $this->actingAs($manager)
            ->post(route('comercial.matriz.services.activate', $service))
            ->assertRedirect();

        $activateLog = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'service')
            ->where('action', 'activate')
            ->where('auditable_id', $service->id)
            ->firstOrFail();

        $this->assertFalse($activateLog->metadata['previous_is_active']);
    }

    public function test_parameter_crud_writes_audit_events(): void
    {
        $manager = $this->parametersManager();

        $this->actingAs($manager)->post(route('comercial.parameters.store', ['type' => 'sectors']), [
            'name' => 'Sector Audit',
            'is_active' => '1',
            'sort_order' => 10,
        ])->assertRedirect();

        $sector = CommercialSector::query()->where('name', 'Sector Audit')->firstOrFail();

        $createLog = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'parameter')
            ->where('action', 'create')
            ->where('auditable_id', $sector->id)
            ->firstOrFail();

        $this->assertSame('sectors', $createLog->metadata['parameter_type']);
        $this->assertSame('Sector Audit', $createLog->metadata['name']);

        $this->actingAs($manager)->patch(route('comercial.parameters.update', [
            'type' => 'sectors',
            'parameterId' => $sector->id,
        ]), [
            'name' => 'Sector Audit Actualizado',
            'is_active' => '0',
            'sort_order' => 20,
        ])->assertRedirect();

        $updateLog = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'parameter')
            ->where('action', 'update')
            ->where('auditable_id', $sector->id)
            ->firstOrFail();

        $this->assertSame('Sector Audit', $updateLog->old_values['name']);
        $this->assertSame('Sector Audit Actualizado', $updateLog->new_values['name']);
        $this->assertTrue($updateLog->old_values['is_active']);
        $this->assertFalse($updateLog->new_values['is_active']);

        $this->actingAs($manager)->delete(route('comercial.parameters.destroy', [
            'type' => 'sectors',
            'parameterId' => $sector->id,
        ]))->assertRedirect();

        $deleteLog = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'parameter')
            ->where('action', 'delete')
            ->firstOrFail();

        $this->assertSame('sectors', $deleteLog->metadata['parameter_type']);
        $this->assertSame('Sector Audit Actualizado', $deleteLog->metadata['name']);
    }

    public function test_matrix_import_writes_single_summary_audit_event(): void
    {
        $manager = $this->matrizManager();
        $path = $this->makeImportSpreadsheet([
            'nit' => '900888005-1',
            'client_name' => 'Import Audit SA',
            'city' => 'Pereira',
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'CT-IMP-AUD',
        ]);

        $file = new UploadedFile(
            $path,
            'matriz.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->actingAs($manager)
            ->post(route('comercial.matriz.clients.import'), ['import_file' => $file])
            ->assertRedirect();

        $this->assertSame(1, AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'import')
            ->where('action', 'matrix')
            ->count());

        $log = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'import')
            ->where('action', 'matrix')
            ->firstOrFail();

        $this->assertSame(1, $log->metadata['clients_created']);
        $this->assertSame(0, $log->metadata['clients_updated']);
        $this->assertSame(1, $log->metadata['services_created']);
        $this->assertArrayHasKey('skipped', $log->metadata);
        $this->assertArrayHasKey('empty_rows', $log->metadata);
    }

    public function test_clients_export_writes_clients_excel_audit_event(): void
    {
        $manager = $this->matrizManager();
        $manager->givePermissionTo('comercial.matriz.view');

        CommercialClient::query()->create([
            'nit' => '900888006-1',
            'name' => 'Export Audit SA',
            'city' => 'Cali',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $this->actingAs($manager)->get(route('comercial.matriz.clients.export', [
            'q' => 'Export Audit',
            'city' => 'Cali',
        ]))->assertOk();

        $log = AuditLog::query()
            ->where('module', 'commercial')
            ->where('event_type', 'export')
            ->where('action', 'clients_excel')
            ->firstOrFail();

        $this->assertSame('comercial', $log->area);
        $this->assertSame(1, $log->metadata['row_count']);
        $this->assertSame('Export Audit', $log->metadata['filters']['q']);
        $this->assertSame('Cali', $log->metadata['filters']['city']);
    }

    public function test_audit_disabled_skips_commercial_writes(): void
    {
        Config::set('audit.enabled', false);

        $manager = $this->matrizManager();

        $this->actingAs($manager)->post(route('comercial.matriz.clients.store'), [
            'nit' => '900888007-1',
            'name' => 'Sin Audit SA',
            'city' => 'Cali',
        ])->assertRedirect();

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'commercial',
            'event_type' => 'client',
            'action' => 'create',
        ]);
    }

    private function matrizManager(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.manage');

        return $user;
    }

    private function parametersManager(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('manage.commercial.parameters');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function makeImportSpreadsheet(array $row): string
    {
        $columns = array_keys(config('commercial_matrix.import_columns'));
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(config('commercial_matrix.sheet_name', 'Matriz comercial'));

        foreach ($columns as $index => $key) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $key);
            $sheet->setCellValue($col.'2', config('commercial_matrix.import_columns')[$key] ?? $key);
        }

        foreach ($columns as $index => $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'3', $row[$key]);
        }

        $path = tempnam(sys_get_temp_dir(), 'comercial-audit-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}

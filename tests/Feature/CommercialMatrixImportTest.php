<?php

namespace Tests\Feature;

use App\Models\CommercialClient;
use App\Models\CommercialClientDocumentItem;
use App\Models\CommercialService;
use App\Models\User;
use App\Support\CommercialDocumentCatalog;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CommercialMatrixImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_import_template_download_requires_manage_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false, 'area_key' => 'comercial']);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('comercial.matriz.view');

        $this->actingAs($viewer)
            ->get(route('comercial.matriz.clients.import-template'))
            ->assertForbidden();

        $manager = $this->matrizManager();

        $this->actingAs($manager)
            ->get(route('comercial.matriz.clients.import-template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_import_creates_client_service_and_checklist(): void
    {
        $manager = $this->matrizManager();
        $path = $this->makeImportSpreadsheet([
            'nit' => '900555001',
            'client_name' => 'Cliente Importado SA',
            'city' => 'Bogota',
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'CT-001',
            'service_description' => 'Vigilancia perimetral',
            'doc_rut' => 'OK',
            'doc_contract' => 'X',
        ]);

        $file = new UploadedFile($path, 'matriz.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($manager)
            ->post(route('comercial.matriz.clients.import'), ['import_file' => $file])
            ->assertRedirect(route('comercial.matriz.clients.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('import_result');

        $client = CommercialClient::query()->where('nit', '900555001')->first();
        $this->assertNotNull($client);
        $this->assertSame('Cliente Importado SA', $client->name);

        $this->assertDatabaseHas('commercial_services', [
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'CT-001',
        ]);

        $this->assertDatabaseHas('commercial_client_document_items', [
            'commercial_client_id' => $client->id,
            'document_key' => 'doc_rut',
            'status' => CommercialDocumentCatalog::DOC_OK,
        ]);

        $this->assertDatabaseHas('commercial_client_document_items', [
            'commercial_client_id' => $client->id,
            'document_key' => 'doc_contract',
            'status' => CommercialDocumentCatalog::DOC_X,
        ]);
    }

    public function test_import_updates_existing_client_and_service(): void
    {
        $manager = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900555002',
            'name' => 'Nombre Anterior',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
            'contract_number' => 'CT-OLD',
            'service_description' => 'Descripcion anterior',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $path = $this->makeImportSpreadsheet([
            'nit' => '900555002',
            'client_name' => 'Nombre Actualizado',
            'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
            'contract_number' => 'CT-OLD',
            'service_description' => 'Monitoreo CCTV',
            'doc_fo_co_02' => 'Pendiente',
        ]);

        $file = new UploadedFile($path, 'matriz.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($manager)->post(route('comercial.matriz.clients.import'), ['import_file' => $file]);

        $client->refresh();
        $this->assertSame('Nombre Actualizado', $client->name);

        $this->assertDatabaseHas('commercial_services', [
            'commercial_client_id' => $client->id,
            'contract_number' => 'CT-OLD',
            'service_description' => 'Monitoreo CCTV',
        ]);

        $this->assertDatabaseHas('commercial_client_document_items', [
            'commercial_client_id' => $client->id,
            'document_key' => 'doc_fo_co_02',
            'status' => CommercialDocumentCatalog::DOC_PENDING,
        ]);
    }

    public function test_export_import_template_includes_service_data(): void
    {
        $manager = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900555003',
            'name' => 'Cliente Export',
            'city' => 'Medellin',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_OCASIONALES,
            'contract_number' => 'CT-EXP',
            'service_description' => 'Evento especial',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        CommercialClientDocumentItem::query()->where('commercial_client_id', $client->id)
            ->where('document_key', 'doc_rut')
            ->update(['status' => CommercialDocumentCatalog::DOC_OK]);

        $response = $this->actingAs($manager)->get(route('comercial.matriz.clients.export-import-template'));

        $response->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'comercial-export-');
        file_put_contents($temp, $response->streamedContent());
        $sheet = IOFactory::load($temp)->getSheetByName(config('commercial_matrix.sheet_name'));
        $this->assertNotNull($sheet);

        $columns = array_keys(config('commercial_matrix.import_columns'));
        $nitCol = Coordinate::stringFromColumnIndex(array_search('nit', $columns, true) + 1);

        $this->assertSame('900555003', (string) $sheet->getCell($nitCol.'3')->getValue());
    }

    public function test_export_import_template_redirects_when_no_services(): void
    {
        $manager = $this->matrizManager();

        $this->actingAs($manager)
            ->get(route('comercial.matriz.clients.export-import-template', ['q' => 'inexistente-xyz']))
            ->assertRedirect(route('comercial.matriz.clients.index', ['q' => 'inexistente-xyz']))
            ->assertSessionHasErrors('export');
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

        $path = tempnam(sys_get_temp_dir(), 'comercial-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}

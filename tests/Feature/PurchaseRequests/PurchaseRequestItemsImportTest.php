<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PurchaseRequestItemsImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_import_template_requires_create_permission(): void
    {
        $viewer = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('purchase.tab.my_requests');

        $this->actingAs($viewer)
            ->get(route('purchase-requests.items.import-template', ['module' => 'compras']))
            ->assertForbidden();

        $creator = $this->creatorUser();

        $this->actingAs($creator)
            ->get(route('purchase-requests.items.import-template', ['module' => 'compras']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_import_items_returns_json_for_table_prefill(): void
    {
        $creator = $this->creatorUser();
        $path = $this->makeItemsSpreadsheet([
            [
                'cantidad' => 3,
                'descripcion' => 'Cascos de seguridad',
                'referencia' => 'CASCO-01',
                'utilizacion' => 'Obra',
                'ubicacion' => 'Cali',
            ],
            [
                'cantidad' => 1,
                'descripcion' => 'Guantes nitrilo',
                'referencia' => 'GUA-02',
                'utilizacion' => 'Bodega',
                'ubicacion' => 'Bogota',
            ],
        ]);

        $response = $this->actingAs($creator)->postJson(
            route('purchase-requests.items.import', ['module' => 'compras']),
            ['import_file' => new UploadedFile($path, 'items.xlsx', null, null, true)],
        );

        $response->assertOk()
            ->assertJsonPath('items.0.descripcion', 'Cascos de seguridad')
            ->assertJsonPath('items.0.cantidad', 3)
            ->assertJsonPath('items.1.referencia', 'GUA-02')
            ->assertJsonCount(2, 'items');
    }

    public function test_import_items_rejects_file_without_required_columns(): void
    {
        $creator = $this->creatorUser();
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'foo');
        $sheet->setCellValue('A2', 'Foo');
        $sheet->setCellValue('A3', 'bar');
        $path = tempnam(sys_get_temp_dir(), 'pr-items-bad-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($creator)->postJson(
            route('purchase-requests.items.import', ['module' => 'compras']),
            ['import_file' => new UploadedFile($path, 'bad.xlsx', null, null, true)],
        )->assertStatus(422);
    }

    private function creatorUser(): User
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo(['purchase.tab.create', 'purchase.tab.my_requests']);

        return $user;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function makeItemsSpreadsheet(array $rows): string
    {
        $columns = array_keys(config('purchase-requests.items_import_columns'));
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $index => $key) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $key);
            $sheet->setCellValue($col.'2', $key);
        }

        foreach ($rows as $rowOffset => $row) {
            $excelRow = $rowOffset + 3;
            foreach ($columns as $index => $key) {
                if (! array_key_exists($key, $row)) {
                    continue;
                }
                $col = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue($col.$excelRow, $row[$key]);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'pr-items-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}

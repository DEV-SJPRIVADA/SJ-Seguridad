<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseProvider;
use App\Models\User;
use App\Services\PurchaseRequests\Providers\PurchaseProviderImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PurchaseProviderImportTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->editor = User::factory()->create();
        $this->editor->givePermissionTo('purchase.providers.edit');
    }

    public function test_imports_only_providers_and_upserts_by_identificacion(): void
    {
        PurchaseProvider::factory()->create([
            'identificacion' => 'EXIST-1',
            'nombre' => 'VIEJO NOMBRE',
        ]);

        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'EXIST-1',
                'NOMBRE' => 'proveedor actualizado',
                'TELEFONO' => '300 123 4567',
                'CORREO_ELE' => 'Nuevo@Correo.com',
                'PROVEEDOR' => 'SI',
                'ESTADO' => 'INACTIVO',
                'DIAS_PAGO' => 30,
            ],
            [
                'IDENTIFICA' => 'NUEVO-1',
                'NOMBRE' => 'proveedor nuevo',
                'TELEFONO' => '6015550000',
                'CORREO_ELE' => 'nuevo@correo.com',
                'PROVEEDOR' => 'SI',
                'ESTADO' => 'ACTIVO',
                'DIAS_PAGO' => '',
            ],
            [
                'IDENTIFICA' => 'CLIENTE-1',
                'NOMBRE' => 'solo cliente',
                'PROVEEDOR' => 'NO',
                'ESTADO' => 'ACTIVO',
            ],
        ]);

        $report = app(PurchaseProviderImportService::class)
            ->import($path, false, null, $this->editor->id);

        $this->assertSame(1, $report['created']);
        $this->assertSame(1, $report['updated']);
        $this->assertSame(1, $report['skipped']);
        $this->assertSame(0, $report['errors_count']);

        $updated = PurchaseProvider::query()->where('identificacion', 'EXIST-1')->first();
        $this->assertNotNull($updated);
        $this->assertSame('PROVEEDOR ACTUALIZADO', $updated->nombre);
        $this->assertSame('3001234567', $updated->telefono);
        $this->assertSame('Nuevo@Correo.com', $updated->correo);
        $this->assertSame(PurchaseProvider::ESTADO_INACTIVO, $updated->estado);
        $this->assertSame(30, (int) $updated->dias_pago);

        $created = PurchaseProvider::query()->where('identificacion', 'NUEVO-1')->first();
        $this->assertNotNull($created);
        $this->assertSame('PROVEEDOR NUEVO', $created->nombre);
        $this->assertNull($created->dias_pago);

        $this->assertNull(
            PurchaseProvider::query()->where('identificacion', 'CLIENTE-1')->first()
        );
    }

    public function test_dry_run_reports_without_persisting(): void
    {
        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'DRY-1',
                'NOMBRE' => 'proveedor dry',
                'PROVEEDOR' => 'SI',
                'ESTADO' => 'ACTIVO',
            ],
        ]);

        $report = app(PurchaseProviderImportService::class)->import($path, true);

        $this->assertTrue($report['dry_run']);
        $this->assertSame(1, $report['created']);
        $this->assertSame(0, PurchaseProvider::query()->count());
    }

    public function test_command_dry_run_does_not_persist(): void
    {
        $path = $this->makeSpreadsheet([
            ['IDENTIFICA' => 'CMD-1', 'NOMBRE' => 'proveedor cmd', 'PROVEEDOR' => 'SI'],
        ]);

        $this->artisan('purchase-providers:import', [
            'path' => $path,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('purchase_providers', ['identificacion' => 'CMD-1']);
    }

    public function test_import_route_requires_permission(): void
    {
        $this->get(route('purchase-requests.providers.template', ['module' => 'compras']))
            ->assertRedirect(route('login'));

        $path = $this->makeSpreadsheet([
            ['IDENTIFICA' => 'X-1', 'NOMBRE' => 'X', 'PROVEEDOR' => 'SI'],
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('purchase-requests.providers.import', ['module' => 'compras']), [
                'import_file' => new UploadedFile(
                    $path,
                    'vine01.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true,
                ),
            ])
            ->assertForbidden();
    }

    public function test_import_route_processes_file_and_reports(): void
    {
        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'ROUTE-1',
                'NOMBRE' => 'proveedor ruta',
                'PROVEEDOR' => 'SI',
                'ESTADO' => 'ACTIVO',
                'DIAS_PAGO' => 15,
            ],
            [
                'IDENTIFICA' => 'ROUTE-2',
                'NOMBRE' => 'cliente ruta',
                'PROVEEDOR' => 'NO',
            ],
        ]);

        $response = $this->actingAs($this->editor)
            ->post(route('purchase-requests.providers.import', ['module' => 'compras']), [
                'import_file' => new UploadedFile(
                    $path,
                    'vine01.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true,
                ),
            ]);

        $response->assertRedirect(route('purchase-requests.providers.index', ['module' => 'compras']));
        $response->assertSessionHas('import_done', true);
        $response->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 1
                && $result['updated'] === 0
                && $result['skipped'] === 1
                && $result['failures_count'] === 0;
        });

        $this->assertDatabaseHas('purchase_providers', [
            'identificacion' => 'ROUTE-1',
        ]);
        $this->assertDatabaseMissing('purchase_providers', [
            'identificacion' => 'ROUTE-2',
        ]);
    }

    public function test_import_result_is_shown_in_modal_with_downloadable_report(): void
    {
        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'OK-1',
                'NOMBRE' => 'proveedor ok',
                'PROVEEDOR' => 'SI',
                'ESTADO' => 'ACTIVO',
            ],
            [
                'IDENTIFICA' => 'ERR-1',
                'NOMBRE' => '',
                'PROVEEDOR' => 'SI',
                'ESTADO' => 'ACTIVO',
            ],
        ]);

        $response = $this->actingAs($this->editor)
            ->post(route('purchase-requests.providers.import', ['module' => 'compras']), [
                'import_file' => new UploadedFile(
                    $path,
                    'vine01.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true,
                ),
            ]);

        $response->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 1
                && $result['failures_count'] === 1
                && ! empty($result['report_token']);
        });

        $token = (string) session('import_result')['report_token'];

        $this->actingAs($this->editor)
            ->get(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('Resultado de carga masiva')
            ->assertSee('title="Descargar reporte (.xlsx)"', false)
            ->assertSee('el nombre es obligatorio.');

        $download = $this->actingAs($this->editor)
            ->get(route('purchase-requests.providers.import-report', [
                'module' => 'compras',
                'token' => $token,
            ]));

        $download->assertOk();
        $this->assertStringContainsString(
            'reporte_importacion_proveedores',
            (string) $download->headers->get('content-disposition'),
        );
    }

    public function test_import_report_download_rejects_other_users(): void
    {
        // Un archivo sin filas válidas se rechaza, así que se agrega una fila válida.
        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'OK-2',
                'NOMBRE' => 'proveedor ok 2',
                'PROVEEDOR' => 'SI',
            ],
            [
                'IDENTIFICA' => 'ERR-2',
                'NOMBRE' => '',
                'PROVEEDOR' => 'SI',
            ],
        ]);

        $response = $this->actingAs($this->editor)
            ->post(route('purchase-requests.providers.import', ['module' => 'compras']), [
                'import_file' => new UploadedFile(
                    $path,
                    'vine01.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true,
                ),
            ]);

        $response->assertSessionHas('import_result');

        $token = (string) session('import_result')['report_token'];
        $this->assertNotEmpty($token);

        $otherEditor = User::factory()->create();
        $otherEditor->givePermissionTo('purchase.providers.edit');

        $this->actingAs($otherEditor)
            ->get(route('purchase-requests.providers.import-report', [
                'module' => 'compras',
                'token' => $token,
            ]))
            ->assertForbidden();
    }

    public function test_import_route_rejects_file_without_required_headers(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'OTRA_COLUMNA');
        $path = tempnam(sys_get_temp_dir(), 'vine01-bad-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($this->editor)
            ->from(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->post(route('purchase-requests.providers.import', ['module' => 'compras']), [
                'import_file' => new UploadedFile(
                    $path,
                    'vine01.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true,
                ),
            ])
            ->assertRedirect(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->assertSessionHasErrors('import_file');
    }

    public function test_template_download_returns_xlsx(): void
    {
        $response = $this->actingAs($this->editor)
            ->get(route('purchase-requests.providers.template', ['module' => 'compras']));

        $response->assertOk();
        $this->assertStringContainsString(
            'plantilla_proveedores_VINE01.xlsx',
            (string) $response->headers->get('content-disposition'),
        );

        $path = tempnam(sys_get_temp_dir(), 'vine01-tpl-').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $sheet = IOFactory::load($path)->getActiveSheet();
        $headers = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($col = 1; $col <= $maxCol; $col++) {
            $headers[] = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).'1')->getValue();
        }

        $this->assertContains('CONTACTO', $headers);
        $this->assertContains('DIRECCION', $headers);
        $this->assertContains('CIUDAD', $headers);
        $this->assertContains('FORMA_PAGO', $headers);
        $this->assertContains('DIAS_PAGO', $headers);
    }

    public function test_imports_contacto_ciudad_and_forma_pago(): void
    {
        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'FOAD-1',
                'NOMBRE' => 'proveedor foad',
                'PROVEEDOR' => 'SI',
                'CONTACTO' => 'juan perez',
                'CIUDAD' => 'cali',
                'FORMA_PAGO' => 'transferencia',
                'DIAS_PAGO' => 45,
            ],
        ]);

        $report = app(PurchaseProviderImportService::class)
            ->import($path, false, null, $this->editor->id);

        $this->assertSame(1, $report['created']);
        $this->assertSame(0, $report['errors_count']);

        $provider = PurchaseProvider::query()->where('identificacion', 'FOAD-1')->firstOrFail();
        $this->assertSame('JUAN PEREZ', $provider->contacto);
        // La ciudad se guarda tal como viene del archivo, sin normalizar.
        $this->assertSame('cali', $provider->ciudad);
        $this->assertSame('TRANSFERENCIA', $provider->forma_pago);
        $this->assertSame(45, (int) $provider->dias_pago);
    }

    public function test_saves_ciudad_as_coming_without_catalog_validation(): void
    {
        PurchaseProvider::factory()->create([
            'identificacion' => 'CITY-UP',
            'nombre' => 'VIEJO',
            'ciudad' => 'Cali',
        ]);

        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'CITY-NEW',
                'NOMBRE' => 'proveedor ciudad dane',
                'PROVEEDOR' => 'SI',
                'CIUDAD' => '76001-CALI',
            ],
            [
                'IDENTIFICA' => 'CITY-UP',
                'NOMBRE' => 'proveedor ciudad actualizada',
                'PROVEEDOR' => 'SI',
                'CIUDAD' => '77005-.',
            ],
            [
                'IDENTIFICA' => 'CITY-EMPTY',
                'NOMBRE' => 'proveedor ciudad vacia',
                'PROVEEDOR' => 'SI',
                'CIUDAD' => '',
            ],
        ]);

        $report = app(PurchaseProviderImportService::class)
            ->import($path, false, null, $this->editor->id);

        $this->assertSame(2, $report['created']);
        $this->assertSame(1, $report['updated']);
        $this->assertSame(0, $report['errors_count']);

        // Se guarda tal como viene (código DANE incluido) y en actualización reemplaza.
        $this->assertSame(
            '76001-CALI',
            PurchaseProvider::query()->where('identificacion', 'CITY-NEW')->value('ciudad'),
        );
        $this->assertSame(
            '77005-.',
            PurchaseProvider::query()->where('identificacion', 'CITY-UP')->value('ciudad'),
        );
        $this->assertNull(
            PurchaseProvider::query()->where('identificacion', 'CITY-EMPTY')->value('ciudad'),
        );
    }

    public function test_no_valid_rows_message_reports_first_failure(): void
    {
        $path = $this->makeSpreadsheet([
            ['IDENTIFICA' => 'BAD-1', 'NOMBRE' => '', 'PROVEEDOR' => 'SI'],
            ['IDENTIFICA' => 'BAD-2', 'NOMBRE' => '', 'PROVEEDOR' => 'SI'],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'El archivo no contiene filas de proveedor válidas (PROVEEDOR = SI). 2 fila(s) con error de datos; primera: Fila 2: el nombre es obligatorio.'
        );

        app(PurchaseProviderImportService::class)->import($path);
    }

    public function test_dias_pago_tolerante_extracte_dias_y_pasa_texto_a_forma_pago(): void
    {
        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'DIAS-T1',
                'NOMBRE' => 'proveedor dias texto 1',
                'PROVEEDOR' => 'SI',
                'FORMA_PAGO' => '',
                'DIAS_PAGO' => '15 DIAS',
            ],
            [
                'IDENTIFICA' => 'DIAS-T2',
                'NOMBRE' => 'proveedor dias texto 2',
                'PROVEEDOR' => 'SI',
                'FORMA_PAGO' => '',
                'DIAS_PAGO' => 'CONTADO',
            ],
            [
                'IDENTIFICA' => 'DIAS-T3',
                'NOMBRE' => 'proveedor dias texto 3',
                'PROVEEDOR' => 'SI',
                'FORMA_PAGO' => '',
                'DIAS_PAGO' => '50 % ANTICIPO Y 50% CONTRAENTREGA',
            ],
            [
                'IDENTIFICA' => 'DIAS-T4',
                'NOMBRE' => 'proveedor dias texto 4',
                'PROVEEDOR' => 'SI',
                'FORMA_PAGO' => 'EFECTIVO',
                'DIAS_PAGO' => '20 DÍAS',
            ],
        ]);

        $report = app(PurchaseProviderImportService::class)
            ->import($path, false, null, $this->editor->id);

        $this->assertSame(4, $report['created']);
        $this->assertSame(0, $report['errors_count']);

        $t1 = PurchaseProvider::query()->where('identificacion', 'DIAS-T1')->firstOrFail();
        $this->assertSame(15, (int) $t1->dias_pago);
        $this->assertNull($t1->forma_pago);

        $t2 = PurchaseProvider::query()->where('identificacion', 'DIAS-T2')->firstOrFail();
        $this->assertNull($t2->dias_pago);
        $this->assertSame('CONTADO', $t2->forma_pago);

        $t3 = PurchaseProvider::query()->where('identificacion', 'DIAS-T3')->firstOrFail();
        $this->assertNull($t3->dias_pago);
        $this->assertSame('50 % ANTICIPO Y 50% CONTRAENTREGA', $t3->forma_pago);

        $t4 = PurchaseProvider::query()->where('identificacion', 'DIAS-T4')->firstOrFail();
        $this->assertSame(20, (int) $t4->dias_pago);
        $this->assertSame('EFECTIVO', $t4->forma_pago);
    }

    public function test_imports_direccion_in_uppercase_and_keeps_it_when_empty(): void
    {
        PurchaseProvider::factory()->create([
            'identificacion' => 'DIR-IMP-1',
            'nombre' => 'VIEJO',
            'direccion' => 'CALLE ANTERIOR 10',
        ]);

        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'DIR-IMP-1',
                'NOMBRE' => 'proveedor direccion actualizada',
                'PROVEEDOR' => 'SI',
                'DIRECCION' => 'avenida nueva 45 # 10-20',
            ],
            [
                'IDENTIFICA' => 'DIR-IMP-2',
                'NOMBRE' => 'proveedor direccion vacia',
                'PROVEEDOR' => 'SI',
                'DIRECCION' => '',
            ],
        ]);

        $report = app(PurchaseProviderImportService::class)
            ->import($path, false, null, $this->editor->id);

        $this->assertSame(1, $report['created']);
        $this->assertSame(1, $report['updated']);
        $this->assertSame(0, $report['errors_count']);

        $updated = PurchaseProvider::query()->where('identificacion', 'DIR-IMP-1')->firstOrFail();
        $this->assertSame('AVENIDA NUEVA 45 # 10-20', $updated->direccion);

        $created = PurchaseProvider::query()->where('identificacion', 'DIR-IMP-2')->firstOrFail();
        $this->assertNull($created->direccion);
    }

    public function test_import_update_with_empty_direccion_keeps_current_value(): void
    {
        PurchaseProvider::factory()->create([
            'identificacion' => 'DIR-IMP-3',
            'nombre' => 'VIEJO',
            'direccion' => 'CALLE ANTERIOR 10',
        ]);

        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'DIR-IMP-3',
                'NOMBRE' => 'nombre actualizado',
                'PROVEEDOR' => 'SI',
                'DIRECCION' => '',
            ],
        ]);

        app(PurchaseProviderImportService::class)->import($path, false, null, $this->editor->id);

        $provider = PurchaseProvider::query()->where('identificacion', 'DIR-IMP-3')->firstOrFail();
        $this->assertSame('NOMBRE ACTUALIZADO', $provider->nombre);
        $this->assertSame('CALLE ANTERIOR 10', $provider->direccion);
    }

    public function test_legacy_file_without_new_columns_does_not_wipe_existing_values(): void
    {
        PurchaseProvider::factory()->create([
            'identificacion' => 'LEGACY-1',
            'nombre' => 'VIEJO',
            'contacto' => 'ANA LOPEZ',
            'direccion' => 'CARRERA 5 # 3-40',
            'ciudad' => 'Cali',
            'forma_pago' => 'EFECTIVO',
        ]);

        $path = $this->makeSpreadsheet([
            [
                'IDENTIFICA' => 'LEGACY-1',
                'NOMBRE' => 'nombre actualizado',
                'PROVEEDOR' => 'SI',
                'ESTADO' => 'ACTIVO',
            ],
        ], ['IDENTIFICA', 'NOMBRE', 'TELEFONO', 'CORREO_ELE', 'PROVEEDOR', 'ESTADO', 'DIAS_PAGO']);

        $report = app(PurchaseProviderImportService::class)->import($path);

        $this->assertSame(1, $report['updated']);
        $this->assertSame(0, $report['errors_count']);

        $provider = PurchaseProvider::query()->where('identificacion', 'LEGACY-1')->firstOrFail();
        $this->assertSame('NOMBRE ACTUALIZADO', $provider->nombre);
        $this->assertSame('ANA LOPEZ', $provider->contacto);
        $this->assertSame('CARRERA 5 # 3-40', $provider->direccion);
        $this->assertSame('Cali', $provider->ciudad);
        $this->assertSame('EFECTIVO', $provider->forma_pago);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>|null  $columns
     */
    private function makeSpreadsheet(array $rows, ?array $columns = null): string
    {
        $columns ??= array_keys(config('purchase-requests.providers_import_columns'));
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($columns as $index => $header) {
                $value = $row[$header] ?? '';
                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex($index + 1).($rowIndex + 2),
                    $value,
                );
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'vine01-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}

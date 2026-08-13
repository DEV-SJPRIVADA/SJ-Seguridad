<?php

namespace Tests\Feature\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Services\GestionHumana\EmployeeFichaCatalogService;
use App\Services\GestionHumana\PayrollCatalogSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFichaCatalogFe028Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_document_type_defaults_include_ce_and_five_codes(): void
    {
        $defaults = config('employee_ficha.document_type_defaults', []);
        $codes = array_column($defaults, 'code');

        $this->assertSame(['C', 'CE', 'N', 'TI', 'PT'], $codes);
    }

    public function test_catalog_types_include_fe028_types(): void
    {
        $types = config('employee_ficha.catalog_types', []);

        foreach ([
            'work_center',
            'linkage_type',
            'account_type',
            'risk_level',
            'workday',
            'ccf',
            'withholding_type',
            'expense_type',
        ] as $type) {
            $this->assertContains($type, $types, "Missing catalog type: {$type}");
        }
    }

    public function test_catalog_service_recognizes_new_catalog_types(): void
    {
        $service = app(EmployeeFichaCatalogService::class);

        $this->assertTrue($service->isValidType('work_center'));
        $this->assertTrue($service->isValidType('ccf'));
        $this->assertTrue($service->isValidType('linkage_type'));
        $this->assertFalse($service->isValidType('invalid_catalog_type'));
    }

    public function test_plantilla_masivos_excludes_nit_column(): void
    {
        $this->assertContains('NITCENTROTB.C15', config('employee_ficha.plantilla_masivos_excluded_columns', []));
    }

    public function test_migration_seeds_ce_document_type_and_static_catalogs(): void
    {
        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'document_type',
            'code' => 'CE',
            'name' => 'Cedula de extranjeria',
        ]);

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'account_type',
            'code' => '1',
            'name' => 'Ahorros',
        ]);

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'workday',
            'code' => '1',
        ]);
    }

    public function test_seed_static_defaults_command_upserts_catalog_items(): void
    {
        PayrollCatalogItem::query()
            ->where('catalog_type', 'risk_level')
            ->delete();

        $stats = app(PayrollCatalogSeeder::class)->seedStaticDefaults();

        $this->assertNotEmpty($stats['risk_level'] ?? null);
        $this->assertGreaterThanOrEqual(5, PayrollCatalogItem::query()->ofType('risk_level')->count());
    }

    public function test_plantilla_masivos_catalog_columns_map_required_selectors(): void
    {
        $map = config('employee_ficha.plantilla_masivos_catalog_columns', []);

        $this->assertSame('document_type', $map['CLASEDOC.C1'] ?? null);
        $this->assertSame('linkage_type', $map['TIPOVNC.N1'] ?? null);
        $this->assertSame('account_type', $map['TIPOCUENTA.N1'] ?? null);
        $this->assertSame('work_center', $map['CODCENTROTB.C10'] ?? null);
        $this->assertSame('ccf', $map['CODCCF.C10'] ?? null);
        $this->assertSame('workday', $map['JORNADA.N1'] ?? null);
        $this->assertArrayNotHasKey('NITCENTROTB.C15', $map);
    }
}

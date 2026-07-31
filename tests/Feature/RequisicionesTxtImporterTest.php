<?php

namespace Tests\Feature;

use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Services\Requisitions\RequisicionesTxtImporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisicionesTxtImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_imports_contratado_row_with_ficha_entry(): void
    {
        User::factory()->create(['is_active' => true]);

        $sample = <<<'TXT'
|                 1 |         3/02/2026 | NIXON FRANCO      | OPERACIONES       | VIGILANTE         | MASCULINO         |                 1 | 75090361          | QUINTERO          | TERMINO           | OPERACIONES       | RENUNCIA          | 6 meses           |    $ 1.750.905,00 |      $ 249.095,00 |                   |                   |                   |                   |                   | SJ SEGURIDAD PRIVADA LTDA                           | MANIZALES         | GRUPO             | 5X2               | EXPERIENCIA       | GALA              | CONTRATAD         | XIMENA            |                   |                   |                   |                   |                   |                 1 |                   |                   |                   |                   | 04-02             |
TXT;

        $path = tempnam(sys_get_temp_dir(), 'req-import-');
        file_put_contents($path, $sample);

        $stats = app(RequisicionesTxtImporter::class)->import($path, false, null, true);

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(1, $stats['ficha_entries']);
        $this->assertSame([], $stats['errors']);

        $requisition = PersonalRequisition::query()->where('code', 'REQ-IMP-0001')->first();

        $this->assertNotNull($requisition);
        $this->assertSame(PersonalRequisition::STATUS_CONTRATADO, $requisition->status);
        $this->assertSame('75090361', $requisition->replacement_document);
        $this->assertSame('operaciones', $requisition->requesting_area_key);

        $this->assertDatabaseHas('personal_requisition_ficha_entries', [
            'personal_requisition_id' => $requisition->id,
            'hired_document' => 'IMP-1',
        ]);
    }

    public function test_skips_duplicate_legacy_codes_on_second_run(): void
    {
        User::factory()->create(['is_active' => true]);

        $sample = '|                 9 |        23/01/2026 | NIXON FRANCO      | OPERACIONES       | SUPERVISOR        | MASCULINO         |                 1 | N/A               | SERVICIO          |                   | OPERACIONES       | CARGO NUEVO       |                   |                   |                   |                   |                   |                   |                   |                   | SJ SEGURIDAD PRIVADA LTDA                           | CALI              | GRUPO             | FULL HORAS        | HABILIDADES       | OVEROL            | CANCELADO         | VALENTINA         |                 1 |                 1 |                 1 |                 1 |                 1 |                 1 |                   |                   |        24/01/2026 |                 1 |                   |';

        $path = tempnam(sys_get_temp_dir(), 'req-import-');
        file_put_contents($path, $sample);

        $importer = app(RequisicionesTxtImporter::class);

        $first = $importer->import($path, false, null, true);
        $second = $importer->import($path);

        $this->assertSame(1, $first['imported']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(1, $second['skipped']);

        $this->assertDatabaseHas('personal_requisitions', [
            'code' => 'REQ-IMP-0009',
            'status' => PersonalRequisition::STATUS_CANCELADA,
        ]);

        $this->assertSame(0, PersonalRequisitionFichaEntry::query()->count());
    }
}

<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\EmployeeFichaNameEncodingHealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFichaNameEncodingHealTest extends TestCase
{
    use RefreshDatabase;

    public function test_heal_repairs_profile_and_entry_names(): void
    {
        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '100200300',
            'full_name' => 'MU?OZ ORDO?EZ JUAN',
            'first_surname' => 'MU?OZ',
            'second_surname' => 'ORDO?EZ',
            'first_name' => 'JUAN',
            'second_name' => null,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '100200300',
            'hired_full_name' => 'MU?OZ ORDO?EZ JUAN',
            'first_surname' => 'MU?OZ',
            'second_surname' => 'ORDO?EZ',
            'first_name' => 'JUAN',
            'second_name' => null,
            'moved_to_ficha_at' => now(),
        ]);

        $stats = app(EmployeeFichaNameEncodingHealService::class)->heal();

        $this->assertSame(1, $stats['updated_profiles']);
        $this->assertGreaterThanOrEqual(1, $stats['updated_entries']);

        $profile->refresh();
        $entry->refresh();

        $this->assertSame('MUÑOZ ORDOÑEZ JUAN', $profile->full_name);
        $this->assertSame('MUÑOZ', $profile->first_surname);
        $this->assertSame('ORDOÑEZ', $profile->second_surname);
        $this->assertSame('MUÑOZ ORDOÑEZ JUAN', $entry->hired_full_name);
        $this->assertSame('MUÑOZ', $entry->first_surname);
    }

    public function test_heal_repairs_leon_as_accented_o(): void
    {
        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '100200301',
            'full_name' => 'LE?N PEREZ',
            'first_surname' => 'LE?N',
            'second_surname' => 'PEREZ',
            'first_name' => null,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        app(EmployeeFichaNameEncodingHealService::class)->heal();

        $profile->refresh();
        $this->assertSame('LEÓN', $profile->first_surname);
        $this->assertSame('LEÓN PEREZ', $profile->full_name);
    }
}

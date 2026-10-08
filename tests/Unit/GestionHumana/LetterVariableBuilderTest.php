<?php

namespace Tests\Unit\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\Letter\LetterVariableBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterVariableBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_includes_lugar_nacimiento_from_profile(): void
    {
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809999999',
            'hired_full_name' => 'Carta Lugar Nacimiento',
            'moved_to_ficha_at' => now(),
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809999999',
            'full_name' => 'Carta Lugar Nacimiento',
            'birth_place' => 'Cali',
            'salary' => 1500000,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subYear()->toDateString(),
            'salary' => 1500000,
        ]);

        $variables = app(LetterVariableBuilder::class)->build($period, $entry, $profile);

        $this->assertSame('Cali', $variables['LUGAR_NACIMIENTO']);
        $this->assertArrayHasKey('LUGAR_NACIMIENTO', config('employee_ficha.letter_placeholders')['Datos del empleado (Perfil)']);
        $this->assertArrayHasKey('SALARIO_EN_LETRAS', config('employee_ficha.letter_placeholders')['Datos del empleado (Perfil)']);

        if (class_exists(\NumberFormatter::class)) {
            $this->assertNotSame('', $variables['SALARIO_EN_LETRAS']);
            $this->assertStringContainsString('PESOS', $variables['SALARIO_EN_LETRAS']);
            $this->assertSame($variables['SALARIO_EN_LETRAS'], $variables['SALARIO_VINCULO_EN_LETRAS']);
        }
    }

    public function test_build_fecha_terminacion_vinculo_minusculas_and_entrega_dotacion(): void
    {
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809888777',
            'hired_full_name' => 'Dotacion Test',
            'moved_to_ficha_at' => now(),
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809888777',
            'full_name' => 'Dotacion Test',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => '2026-06-01',
        ]);

        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => '2025-01-01',
            'termination_date' => '2026-06-01',
            'last_work_day' => '2026-06-01',
        ]);

        $variables = app(LetterVariableBuilder::class)->build($period, $entry, $profile);

        $this->assertSame('1 de Junio del 2026', $variables['FECHA_TERMINACION_VINCULO']);
        $this->assertSame('1 de junio del 2026', $variables['FECHA_TERMINACION_VINCULO_MINUSCULAS']);
        // Lun 1 jun + 3 días contables → mar 2, mié 3, jue 4.
        $this->assertSame('4 de Junio del 2026', $variables['FECHA_ENTREGA_DOTACION']);
        $this->assertArrayHasKey(
            'FECHA_TERMINACION_VINCULO_MINUSCULAS',
            config('employee_ficha.letter_placeholders')['Datos del vinculo (periodo)']
        );
        $this->assertArrayHasKey(
            'FECHA_ENTREGA_DOTACION',
            config('employee_ficha.letter_placeholders')['Datos del vinculo (periodo)']
        );
    }

    public function test_fecha_entrega_dotacion_skips_sundays_and_holidays(): void
    {
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809888778',
            'hired_full_name' => 'Dotacion Semana Santa',
            'moved_to_ficha_at' => now(),
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809888778',
            'full_name' => 'Dotacion Semana Santa',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => '2026-04-01',
        ]);

        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => '2025-01-01',
            'termination_date' => '2026-04-01',
            'last_work_day' => '2026-04-01',
        ]);

        $variables = app(LetterVariableBuilder::class)->build($period, $entry, $profile);

        // Mié 1 abr: salta 2–3 santo, sáb 4 y dom 5 → +1 lun 6, +2 mar 7, +3 mié 8.
        $this->assertSame('8 de Abril del 2026', $variables['FECHA_ENTREGA_DOTACION']);
    }

    public function test_fecha_entrega_dotacion_skips_saturday(): void
    {
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809888779',
            'hired_full_name' => 'Dotacion Viernes',
            'moved_to_ficha_at' => now(),
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809888779',
            'full_name' => 'Dotacion Viernes',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => '2026-06-05',
        ]);

        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => '2025-01-01',
            'termination_date' => '2026-06-05',
            'last_work_day' => '2026-06-05',
        ]);

        $variables = app(LetterVariableBuilder::class)->build($period, $entry, $profile);

        // Vie 5 jun: salta sáb 6, dom 7 y festivo Corpus (lun 8) → +1 mar 9, +2 mié 10, +3 jue 11.
        $this->assertSame('11 de Junio del 2026', $variables['FECHA_ENTREGA_DOTACION']);
    }
}

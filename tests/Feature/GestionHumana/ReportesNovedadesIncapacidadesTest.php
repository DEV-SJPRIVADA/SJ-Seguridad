<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ReportesNovedadesIncapacidad;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReportesNovedadesIncapacidadesTest extends TestCase
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

    public function test_incapacidades_crud_review_ownership_and_export(): void
    {
        $editor = $this->editorUser();
        $reviewer = $this->reviewerUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.incapacidades'))
            ->assertOk()
            ->assertSee('Incapacidades', false)
            ->assertDontSee('listado y formularios en preparación', false);

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload([
                'document_number' => '5005005005',
                'employee_name' => 'Incap GH',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.incapacidades'));

        $row = ReportesNovedadesIncapacidad::query()->where('document_number', '5005005005')->firstOrFail();
        $this->assertSame('EG', $row->tipo_incapacidad);
        $this->assertNull($row->observacion_nomina);
        $this->assertSame(
            (int) Carbon::parse('2026-09-01')->startOfDay()->diffInDays(now()->startOfDay(), false),
            $row->dias_entrega
        );

        $this->actingAs($editor)
            ->from(route('gestion-humana.reportes-novedades.incapacidades'))
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload([
                'tipo_incapacidad' => 'INVALIDO',
            ]))
            ->assertSessionHasErrors('tipo_incapacidad');

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.incapacidades.update', $row), array_merge(
                $this->payload([
                    'document_number' => '5005005005',
                    'employee_name' => 'Incap Editada',
                    'tipo_incapacidad' => 'ARL',
                    'fecha_envio_final' => '2026-09-05',
                ]),
                [
                    'observacion_nomina' => 'No debe',
                    'dias_entrega' => 9,
                ]
            ))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Incap Editada', $row->employee_name);
        $this->assertSame('ARL', $row->tipo_incapacidad);
        $this->assertNull($row->observacion_nomina);
        $this->assertSame(4, $row->dias_entrega);

        $this->actingAs($reviewer)
            ->patch(route('gestion-humana.reportes-novedades.incapacidades.review', $row), [
                'observacion_nomina' => 'OK Nomina',
                'dias_entrega' => 2,
                'employee_name' => 'Hack',
                'tipo_incapacidad' => 'LM',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('OK Nomina', $row->observacion_nomina);
        $this->assertSame(4, $row->dias_entrega);
        $this->assertSame('Incap Editada', $row->employee_name);
        $this->assertSame('ARL', $row->tipo_incapacidad);

        $this->actingAs($reviewer)
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->delete(route('gestion-humana.reportes-novedades.incapacidades.destroy', $row))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.export'))
            ->assertOk();

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.historial', ['id' => $row->id]))
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.incapacidades.destroy', $row))
            ->assertRedirect();

        $this->assertDatabaseMissing('reportes_novedades_incapacidades', ['id' => $row->id]);
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('reportes_novedades.incapacidades.edit');

        return $user;
    }

    private function reviewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('reportes_novedades.incapacidades.review');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'document_number' => '5050505050',
            'employee_name' => 'Empleado Incapacidad',
            'cargo' => 'Guardia',
            'destino' => 'Cliente B',
            'tipo_incapacidad' => 'EG',
            'dias' => 3,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-03',
            'fecha_recepcion' => '2026-09-01',
            'extemporanea' => '0',
            'observaciones' => 'Obs',
        ], $overrides);
    }
}

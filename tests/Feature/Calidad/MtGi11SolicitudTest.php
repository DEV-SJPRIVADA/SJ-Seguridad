<?php

namespace Tests\Feature\Calidad;

use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11Solicitud;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MtGi11SolicitudTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->editor = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $this->editor->givePermissionTo([
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.view',
            'mt_gi_11.matriz.edit',
        ]);
    }

    /**
     * Catálogos mínimos para el formulario: uno de cada tipo.
     */
    private function catalogs(): array
    {
        return [
            'proceso_id' => MtGi11Proceso::query()->firstOrFail()->id,
            'tipo_documento_id' => MtGi11TipoDocumento::query()->firstOrFail()->id,
            'tipo_solicitud_id' => MtGi11TipoSolicitud::query()->firstOrFail()->id,
            'estado_id' => MtGi11Estado::query()->where('code', 'PENDIENTE')->firstOrFail()->id,
            'cargo_responsable_id' => MtGi11CargoResponsable::query()->firstOrCreate(
                ['code' => 'JEFE'],
                ['name' => 'JEFE DE PROCESO', 'is_active' => true],
            )->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge($this->catalogs(), [
            'fecha_solicitud' => now()->toDateString(),
            'consecutivo' => 'MT-GI-11-001',
            'nombre_documento' => 'procedimiento de prueba',
            'codigo_actual' => 'pr-gi-01',
            'version_actual' => '03',
            'justificacion' => 'se requiere actualizar el procedimiento por cambio de alcance',
            'responsable_proceso' => 'juan perez',
            'codigo_estandarizacion' => 'pr-gi-01-v2',
            'fecha_estandarizacion' => null,
            'version_actualizada' => 4,
        ], $overrides);
    }

    public function test_create_form_opens_for_editor(): void
    {
        $this->actingAs($this->editor)
            ->get(route('calidad.mt-gi-11.matriz.create'))
            ->assertOk()
            ->assertSee('Nueva solicitud MT-GI-11');
    }

    public function test_store_persists_uppercase_text(): void
    {
        $response = $this->actingAs($this->editor)
            ->post(route('calidad.mt-gi-11.matriz.store'), $this->payload());

        $solicitud = MtGi11Solicitud::query()->firstOrFail();

        $response->assertRedirect(route('calidad.mt-gi-11.matriz.show', $solicitud));

        // Todo texto libre queda en MAYÚSCULAS.
        $this->assertSame('MT-GI-11-001', $solicitud->consecutivo);
        $this->assertSame('PROCEDIMIENTO DE PRUEBA', $solicitud->nombre_documento);
        $this->assertSame('PR-GI-01', $solicitud->codigo_actual);
        $this->assertSame('03', $solicitud->version_actual);
        $this->assertSame('SE REQUIERE ACTUALIZAR EL PROCEDIMIENTO POR CAMBIO DE ALCANCE', $solicitud->justificacion);
        $this->assertSame('JUAN PEREZ', $solicitud->responsable_proceso);
        $this->assertSame('PR-GI-01-V2', $solicitud->codigo_estandarizacion);

        // El autor queda como dueño del registro.
        $this->assertSame($this->editor->id, $solicitud->created_by);
        $this->assertSame(4, $solicitud->version_actualizada);
    }

    public function test_consecutivo_can_be_repeated_across_solicitudes(): void
    {
        $this->actingAs($this->editor)->post(route('calidad.mt-gi-11.matriz.store'), $this->payload([
            'nombre_documento' => 'primera version',
            'version_actualizada' => 1,
        ]));

        $this->actingAs($this->editor)->post(route('calidad.mt-gi-11.matriz.store'), $this->payload([
            'nombre_documento' => 'segunda version',
            'version_actualizada' => 2,
        ]));

        $this->assertSame(2, MtGi11Solicitud::query()->where('consecutivo', 'MT-GI-11-001')->count());
    }

    public function test_justificacion_is_required(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.mt-gi-11.matriz.store'), $this->payload(['justificacion' => '']))
            ->assertSessionHasErrors('justificacion');

        $this->assertSame(0, MtGi11Solicitud::query()->count());
    }

    public function test_justificacion_rejects_more_than_2000_characters(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.mt-gi-11.matriz.store'), $this->payload(['justificacion' => str_repeat('A', 2001)]))
            ->assertSessionHasErrors('justificacion');
    }

    public function test_fecha_estandarizacion_cannot_precede_fecha_solicitud(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.mt-gi-11.matriz.store'), $this->payload([
                'fecha_solicitud' => '2026-11-10',
                'fecha_estandarizacion' => '2026-11-01',
            ]))
            ->assertSessionHasErrors('fecha_estandarizacion');
    }

    public function test_update_changes_the_record(): void
    {
        $solicitud = MtGi11Solicitud::query()->create($this->payload() + [
            'created_by' => $this->editor->id,
            'justificacion' => 'TEXTO INICIAL',
        ]);

        $this->actingAs($this->editor)
            ->patch(route('calidad.mt-gi-11.matriz.update', $solicitud), $this->payload([
                'nombre_documento' => 'documento actualizado',
            ]))
            ->assertRedirect(route('calidad.mt-gi-11.matriz.show', $solicitud));

        $this->assertSame('DOCUMENTO ACTUALIZADO', $solicitud->refresh()->nombre_documento);
    }

    public function test_delete_removes_record_and_soft_deletes(): void
    {
        $solicitud = MtGi11Solicitud::query()->create($this->payload() + [
            'created_by' => $this->editor->id,
            'justificacion' => 'TEXTO',
        ]);

        $this->actingAs($this->editor)
            ->delete(route('calidad.mt-gi-11.matriz.destroy', $solicitud))
            ->assertRedirect(route('calidad.mt-gi-11.matriz'));

        $this->assertSoftDeleted('calidad_mt_gi_11_solicitudes', ['id' => $solicitud->id]);
    }

    public function test_nueva_version_precarga_consecutivo_y_suma_uno(): void
    {
        $solicitud = MtGi11Solicitud::query()->create($this->payload() + [
            'created_by' => $this->editor->id,
            'justificacion' => 'TEXTO',
        ]);

        $response = $this->actingAs($this->editor)
            ->get(route('calidad.mt-gi-11.matriz.nueva-version', $solicitud));

        $response->assertOk();
        $response->assertSee('Nueva versión de MT-GI-11-001');
        // Consecutivo precargado y versión + 1 (4 → 5).
        $response->assertSee('value="MT-GI-11-001"', false);
        $response->assertSee('value="5"', false);
    }

    public function test_attachments_are_stored_and_downloadable(): void
    {
        Storage::fake('local');

        $this->actingAs($this->editor)->post(route('calidad.mt-gi-11.matriz.store'), $this->payload([
            'attachments' => [UploadedFile::fake()->create('soporte.pdf', 120, 'application/pdf')],
        ]));

        $solicitud = MtGi11Solicitud::query()->firstOrFail();
        $adjunto = $solicitud->adjuntos()->firstOrFail();

        Storage::disk('local')->assertExists($adjunto->stored_path);
        $this->assertSame('soporte.pdf', $adjunto->original_name);

        $this->actingAs($this->editor)
            ->get(route('calidad.mt-gi-11.matriz.adjuntos.download', [$solicitud, $adjunto]))
            ->assertOk();
    }

    public function test_user_without_edit_cannot_create(): void
    {
        $viewer = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $viewer->givePermissionTo([
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.view',
        ]);

        $this->actingAs($viewer)
            ->get(route('calidad.mt-gi-11.matriz.create'))
            ->assertForbidden();
    }
}

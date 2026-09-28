<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionExportApoSetting;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\AcreditacionExportApoSettingSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcreditacionesExportApoParamsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_seeder_creates_exactly_one_settings_row(): void
    {
        $this->seed(AcreditacionExportApoSettingSeeder::class);

        $this->assertSame(1, AcreditacionExportApoSetting::query()->count());

        $settings = AcreditacionExportApoSetting::query()->first();
        $this->assertNotNull($settings);
        $this->assertSame('9005767186', $settings->nit);
        $this->assertSame('SJ SEGURIDAD PRIVADA LTDA', $settings->razon_social);
        $this->assertSame('1', $settings->tipo_documento);
        $this->assertSame('Principal', $settings->tipo_establecimiento);
        $this->assertSame('3043413064', $settings->telefono_r);
        $this->assertSame('MANZANA 8 CASA 27', $settings->direccion_r);
        $this->assertSame('AV 4N26N 39', $settings->direccion_p);
        $this->assertSame('ValledelCauca', $settings->departamento);
        $this->assertSame('CALI', $settings->ciudad);
        $this->assertSame('11', $settings->educacion_bm);
        $this->assertSame('Ninguna', $settings->educacion_s);
        $this->assertSame('Ninguna', $settings->discapacidad);

        $this->seed(AcreditacionExportApoSettingSeeder::class);

        $this->assertSame(1, AcreditacionExportApoSetting::query()->count());
    }

    public function test_editor_can_update_export_apo_params(): void
    {
        $this->seed(AcreditacionExportApoSettingSeeder::class);
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->patch(route('gestion-humana.acreditaciones.catalogo.export-apo-params.update'), $this->validPayload([
                'ciudad' => 'YUMBO',
                'telefono_r' => '3000000000',
            ]))
            ->assertRedirect(route('gestion-humana.acreditaciones.catalogo'))
            ->assertSessionHas('status');

        $settings = AcreditacionExportApoSetting::query()->first();
        $this->assertNotNull($settings);
        $this->assertSame('YUMBO', $settings->ciudad);
        $this->assertSame('3000000000', $settings->telefono_r);
        $this->assertSame((int) $editor->id, (int) $settings->updated_by);
        $this->assertSame(1, AcreditacionExportApoSetting::query()->count());
    }

    public function test_viewer_cannot_update_export_apo_params(): void
    {
        $this->seed(AcreditacionExportApoSettingSeeder::class);
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->patch(route('gestion-humana.acreditaciones.catalogo.export-apo-params.update'), $this->validPayload([
                'ciudad' => 'HACK',
            ]))
            ->assertForbidden();

        $settings = AcreditacionExportApoSetting::query()->first();
        $this->assertNotNull($settings);
        $this->assertSame('CALI', $settings->ciudad);
    }

    public function test_catalogo_page_shows_export_apo_params_for_editor(): void
    {
        $this->seed(AcreditacionExportApoSettingSeeder::class);
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.catalogo'))
            ->assertOk()
            ->assertSee('Parámetros Export Apo', false)
            ->assertSee('9005767186', false)
            ->assertSee('SJ SEGURIDAD PRIVADA LTDA', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge(AcreditacionExportApoSettingSeeder::defaultAttributes(), $overrides);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
            'acreditaciones.edit',
        ]);

        return $user;
    }
}

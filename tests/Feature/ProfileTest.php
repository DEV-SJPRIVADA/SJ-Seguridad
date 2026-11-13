<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Users\UserSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('form-password-field__toggle', false);
        $response->assertSee('update_password_current_password', false);
        $response->assertSee('update_password_password', false);
        $response->assertSee('update_password_password_confirmation', false);
        $response->assertDontSee('Eliminar cuenta', false);
        $response->assertSee('title="Guardar cambios"', false);
        $response->assertSee('title="Actualizar contraseña"', false);
        $response->assertSee('Firma manuscrita', false);
        $response->assertSee('Aún no has subido una firma.', false);
    }

    public function test_delete_account_section_is_never_shown_on_profile(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('super-admin', 'web');
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('Eliminar cuenta', false)
            ->assertDontSee('confirm-user-deletion', false);
    }

    public function test_profile_self_delete_route_is_removed(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('super-admin', 'web');
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertMethodNotAllowed();

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_upload_png_signature_and_it_is_shown(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('mi-firma.png', 160, 60);

        $this->actingAs($user)
            ->post(route('profile.signature.update'), ['signature' => $file])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'signature-updated');

        $user->refresh();
        $this->assertNotNull($user->signature_path);
        Storage::disk('local')->assertExists($user->signature_path);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('title="Quitar firma"', false);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user_signature',
            'action' => 'uploaded',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_signature_upload_rejects_non_png(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->post(route('profile.signature.update'), [
                'signature' => UploadedFile::fake()->image('firma.jpg', 80, 40),
            ])
            ->assertSessionHasErrors('signature');

        $this->assertNull($user->fresh()->signature_path);
    }

    public function test_user_can_delete_own_signature(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        app(UserSignatureService::class)->store($user, UploadedFile::fake()->image('firma.png', 80, 40));
        $path = $user->fresh()->signature_path;

        $this->actingAs($user)
            ->delete(route('profile.signature.destroy'))
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'signature-deleted');

        $this->assertNull($user->fresh()->signature_path);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user_signature',
            'action' => 'deleted',
            'auditable_id' => $user->id,
        ]);
    }
}

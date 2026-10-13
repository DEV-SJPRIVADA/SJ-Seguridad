<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

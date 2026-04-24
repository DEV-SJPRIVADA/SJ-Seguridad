<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_keep_an_authenticated_session(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
            'password' => 'Temporal123!',
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Temporal123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}

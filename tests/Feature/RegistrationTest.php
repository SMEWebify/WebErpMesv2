<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use App\Providers\RouteServiceProvider;

class RegistrationTest extends TestCase
{
    public function test_registration_is_disabled_by_default()
    {
        // Public sign-up is gated behind REGISTRATION_ENABLED (default false),
        // so the /register routes are not registered at all.
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_screen_can_be_rendered_when_enabled()
    {
        $this->enableRegistration();

        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register_when_enabled()
    {
        $this->enableRegistration();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    private function enableRegistration(): void
    {
        config(['branding.registration_enabled' => true]);

        // Re-evaluate routes/auth.php now that the flag is on so the gated
        // registration routes become available (same web middleware as boot).
        Route::middleware('web')->group(base_path('routes/auth.php'));
    }
}

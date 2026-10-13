<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginModeTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $mode): \Illuminate\Testing\TestResponse
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        return $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
            'modeView' => $mode,
        ]);
    }

    public function test_workshop_mode_opens_the_workshop(): void
    {
        $this->login('workshop')->assertRedirect(RouteServiceProvider::WORKSHOP);
    }

    public function test_desktop_mode_opens_the_dashboard(): void
    {
        $this->login('desktop')->assertRedirect(RouteServiceProvider::HOME);
    }

    /** Le choix explicite de l'atelier l'emporte sur la page mémorisée avant la connexion. */
    public function test_workshop_mode_wins_over_the_intended_page(): void
    {
        $this->withSession(['url.intended' => url('/fr/quotes')]);

        $this->login('workshop')->assertRedirect(RouteServiceProvider::WORKSHOP);
    }

    /** En mode bureau, on revient sur la page demandée avant la connexion. */
    public function test_desktop_mode_keeps_the_intended_page(): void
    {
        $this->withSession(['url.intended' => url('/fr/quotes')]);

        $this->login('desktop')->assertRedirect(url('/fr/quotes'));
    }
}

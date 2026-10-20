<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_page_is_available(): void
    {
        $this->withoutVite()->get('/login')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
    }

    public function test_valid_credentials_authenticate_and_rotate_the_session(): void
    {
        $user = User::factory()->create(['email' => 'test@example.test']);
        $this->withSession(['initial' => true]);
        $previousId = session()->getId();

        $this->post('/login', ['email' => 'TEST@EXAMPLE.TEST', 'password' => 'password'])
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousId, session()->getId());
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $user = User::factory()->create();
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_failed_attempts_temporarily_block_even_a_correct_password(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_rejects_invalid_input_without_reflecting_the_password(): void
    {
        $this->post('/login', ['email' => 'invalid', 'password' => 'never-reflect-this'])
            ->assertSessionHasErrors('email');
        $this->assertArrayNotHasKey('password', session()->getOldInput());
    }

    public function test_dashboard_never_exposes_password_or_remember_token(): void
    {
        $user = User::factory()->create();
        $this->withoutVite()->actingAs($user)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('auth.user.id', $user->id)
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token'));
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['private_marker' => 'private'])
            ->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertFalse(session()->has('private_marker'));
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_csrf_is_required_outside_the_testing_bypass(): void
    {
        $this->app->instance('env', 'local');
        $this->post('/login', ['email' => 'test@example.test', 'password' => 'password'])
            ->assertStatus(419);
        $this->assertGuest();
    }

    public function test_registration_is_not_publicly_exposed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_create_user_command_hashes_password_and_does_not_grant_a_role(): void
    {
        $this->artisan('checkmaster:create-user')
            ->expectsQuestion('Nom complet', 'Compte de test')
            ->expectsQuestion('Adresse e-mail', 'ACCOUNT@EXAMPLE.TEST')
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'a-long-test-password')
            ->expectsQuestion('Confirmer le mot de passe', 'a-long-test-password')
            ->expectsOutput('Compte créé. Vous pouvez vous connecter.')
            ->assertExitCode(0);

        $user = User::where('email', 'account@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('a-long-test-password', $user->password));
        $this->assertNull($user->getAttribute('role'));
    }
}

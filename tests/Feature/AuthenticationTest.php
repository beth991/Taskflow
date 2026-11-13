<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_register_and_is_authenticated(): void
    {
        $session = app('session')->driver();
        $session->start();
        $previousSessionId = $session->getId();
        $this->withCookie($session->getName(), Crypt::encryptString($previousSessionId));

        $response = $this->post(route('register.store'), [
            'name' => 'Taylor Member',
            'email' => 'taylor@example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $user = User::where('email', 'taylor@example.test')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
        $this->assertNotSame('correct-horse-battery', $user->password);
        $this->assertSame(UserRole::Member, $user->role);
        $this->assertTrue($user->isMember());
        $this->assertNotSame($previousSessionId, app('session')->driver()->getId());
    }

    public function test_public_registration_cannot_assign_the_admin_role(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Taylor Member',
            'email' => 'taylor@example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'role' => User::ROLE_ADMIN,
        ]);

        $user = User::where('email', 'taylor@example.test')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(UserRole::Member, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_registration_validates_required_fields_and_password_confirmation(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_user_can_log_in_with_valid_credentials(): void
    {
        $session = app('session')->driver();
        $session->start();
        $previousSessionId = $session->getId();
        $this->withCookie($session->getName(), Crypt::encryptString($previousSessionId));

        $user = User::factory()->create([
            'email' => 'taylor@example.test',
            'password' => Hash::make('correct-horse-battery'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'taylor@example.test',
            'password' => 'correct-horse-battery',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, app('session')->driver()->getId());
    }

    public function test_a_password_at_the_72_byte_limit_can_register_and_log_in(): void
    {
        $password = str_repeat('a', 72);

        $this->post(route('register.store'), [
            'name' => 'Taylor Member',
            'email' => 'taylor@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => 'taylor@example.test',
            'password' => $password,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_a_password_longer_than_72_bytes_is_rejected(): void
    {
        $password = str_repeat('a', 73);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Taylor Member',
            'email' => 'taylor@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'password' => 'The password must not exceed 72 bytes.',
        ]);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_multibyte_password_is_limited_by_bytes_not_characters(): void
    {
        $password = str_repeat('é', 37);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Taylor Member',
            'email' => 'taylor@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'password' => 'The password must not exceed 72 bytes.',
        ]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        User::factory()->create([
            'email' => 'taylor@example.test',
            'password' => Hash::make('correct-horse-battery'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'taylor@example.test',
            'password' => 'incorrect-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_six_attempts(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'unknown@example.test',
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_registration_is_rate_limited_after_six_attempts(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('register.store'), [])->assertSessionHasErrors(['name', 'email', 'password']);
        }

        $this->post(route('register.store'), [])->assertTooManyRequests();
    }

    public function test_an_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guests_can_view_the_registration_and_login_forms(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create your account');
        $this->get(route('login'))->assertOk()->assertSee('Log in to TaskFlow');
    }

    public function test_authenticated_users_can_view_the_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'Taylor Member']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome, Taylor Member');
    }
}

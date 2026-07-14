<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'SenhaSegura@123',
        ]);

        $user->forceFill([
            'status' => UserStatus::Active,
            'approved_at' => now(),
        ])->save();

        $response = $this->postJson('/login', [
            'email' => 'ADMIN@EXAMPLE.COM',
            'password' => 'SenhaSegura@123',
            'remember' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Autenticação realizada com sucesso.')
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.status', UserStatus::Active->value);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@example.com',
            'password' => 'SenhaSegura@123',
        ]);

        $user->forceFill([
            'status' => UserStatus::Active,
            'approved_at' => now(),
        ])->save();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'SenhaIncorreta@456',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_pending_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'pendente@example.com',
            'password' => 'SenhaSegura@123',
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'SenhaSegura@123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Sua conta aguarda aprovação de um administrador.',
            );

        $this->assertGuest();
    }

    public function test_blocked_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'bloqueado@example.com',
            'password' => 'SenhaSegura@123',
        ]);

        $user->forceFill([
            'status' => UserStatus::Blocked,
            'blocked_at' => now(),
        ])->save();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'SenhaSegura@123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Sua conta está bloqueada. Entre em contato com um administrador.',
            );

        $this->assertGuest();
    }

    public function test_protected_user_route_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_profile_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => 'SenhaSegura@123',
        ]);

        $user->forceFill([
            'status' => UserStatus::Active,
            'approved_at' => now(),
        ])->save();

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'SenhaSegura@123',
        ])->assertOk();

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->postJson('/logout')->assertNoContent();

        $this->assertGuest('web');
    }
}

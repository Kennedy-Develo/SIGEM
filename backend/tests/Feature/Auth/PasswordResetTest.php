<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertAccepted()
            ->assertJson([
                'message' => 'Se existir uma conta com este e-mail, enviaremos as instruções para redefinir a senha.',
            ]);

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
        );
    }

    public function test_unknown_email_receives_the_same_safe_response(): void
    {
        Notification::fake();

        $response = $this->postJson('/forgot-password', [
            'email' => 'inexistente@example.com',
        ]);

        $response
            ->assertAccepted()
            ->assertJson([
                'message' => 'Se existir uma conta com este e-mail, enviaremos as instruções para redefinir a senha.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SenhaAntiga@123'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Senha redefinida com sucesso. Você já pode entrar com a nova senha.',
            ]);

        $this->assertTrue(
            Hash::check(
                'NovaSenha@123',
                $user->fresh()->password,
            ),
        );
    }

    public function test_password_cannot_be_reset_with_an_invalid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SenhaAntiga@123'),
        ]);

        $response = $this->postJson('/reset-password', [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertTrue(
            Hash::check(
                'SenhaAntiga@123',
                $user->fresh()->password,
            ),
        );
    }

    public function test_new_password_must_follow_the_security_rules(): void
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->postJson('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'fraca',
            'password_confirmation' => 'fraca',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}

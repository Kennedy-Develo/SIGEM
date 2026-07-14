<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_an_access_request(): void
    {
        $response = $this->postJson('/register', [
            'name' => '  Maria da Silva  ',
            'email' => 'MARIA@EXAMPLE.COM',
            'password' => 'SenhaSegura@123',
            'password_confirmation' => 'SenhaSegura@123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Maria da Silva')
            ->assertJsonPath('user.email', 'maria@example.com')
            ->assertJsonPath('user.role', UserRole::Reader->value)
            ->assertJsonPath('user.status', UserStatus::Pending->value);

        $user = User::query()
            ->where('email', 'maria@example.com')
            ->firstOrFail();

        $this->assertSame(UserRole::Reader, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertTrue(Hash::check('SenhaSegura@123', $user->password));
        $this->assertGuest('web');
    }

    public function test_registration_rejects_an_existing_email(): void
    {
        User::factory()->create([
            'email' => 'maria@example.com',
        ]);

        $response = $this->postJson('/register', [
            'name' => 'Maria da Silva',
            'email' => 'MARIA@EXAMPLE.COM',
            'password' => 'SenhaSegura@123',
            'password_confirmation' => 'SenhaSegura@123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Maria da Silva',
            'email' => 'maria@example.com',
            'password' => 'senha-fraca',
            'password_confirmation' => 'senha-fraca',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseEmpty('users');
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Maria da Silva',
            'email' => 'maria@example.com',
            'password' => 'SenhaSegura@123',
            'password_confirmation' => 'SenhaDiferente@123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseEmpty('users');
    }
}

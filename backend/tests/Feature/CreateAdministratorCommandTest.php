<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_an_active_administrator(): void
    {
        $password = 'SenhaSegura@123';

        $this->artisan('app:create-admin')
            ->expectsQuestion('Nome completo', 'Administrador SIGEM')
            ->expectsQuestion('E-mail', 'ADMIN@EXAMPLE.COM')
            ->expectsQuestion('Senha', $password)
            ->expectsQuestion('Confirme a senha', $password)
            ->assertExitCode(Command::SUCCESS);

        $administrator = User::query()
            ->where('email', 'admin@example.com')
            ->firstOrFail();

        $this->assertSame('Administrador SIGEM', $administrator->name);
        $this->assertSame(UserRole::Administrator, $administrator->role);
        $this->assertSame(UserStatus::Active, $administrator->status);
        $this->assertTrue($administrator->isAdministrator());
        $this->assertTrue($administrator->isActive());
        $this->assertTrue(Hash::check($password, $administrator->password));
        $this->assertNotNull($administrator->email_verified_at);
        $this->assertNotNull($administrator->approved_at);
    }

    public function test_command_rejects_different_passwords(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nome completo', 'Administrador SIGEM')
            ->expectsQuestion('E-mail', 'admin@example.com')
            ->expectsQuestion('Senha', 'SenhaSegura@123')
            ->expectsQuestion('Confirme a senha', 'OutraSenha@456')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_rejects_a_weak_password(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nome completo', 'Administrador SIGEM')
            ->expectsQuestion('E-mail', 'admin@example.com')
            ->expectsQuestion('Senha', '123456')
            ->expectsQuestion('Confirme a senha', '123456')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('users', 0);
    }
}

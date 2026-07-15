<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/admin/users')
            ->assertUnauthorized();
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $reader = $this->createUser(
            UserRole::Reader,
            UserStatus::Active,
        );

        Sanctum::actingAs($reader);

        $this->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_blocked_administrator_cannot_access_user_management(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Blocked,
        );

        Sanctum::actingAs($administrator);

        $this->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_active_administrator_can_list_pending_users(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        $pendingUser = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
        );

        $this->createUser(
            UserRole::Operator,
            UserStatus::Active,
        );

        Sanctum::actingAs($administrator);

        $this->getJson('/api/admin/users?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingUser->id)
            ->assertJsonPath('data.0.status', UserStatus::Pending->value);
    }

    public function test_administrator_can_approve_user_and_assign_role(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        $pendingUser = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
        );

        Sanctum::actingAs($administrator);

        $this->patchJson("/api/admin/users/{$pendingUser->id}", [
            'role' => UserRole::Operator->value,
            'status' => UserStatus::Active->value,
        ])
            ->assertOk()
            ->assertJsonPath('user.role', UserRole::Operator->value)
            ->assertJsonPath('user.status', UserStatus::Active->value)
            ->assertJsonPath('user.approved_by', $administrator->id);

        $pendingUser->refresh();

        $this->assertSame(UserRole::Operator, $pendingUser->role);
        $this->assertSame(UserStatus::Active, $pendingUser->status);
        $this->assertSame($administrator->id, $pendingUser->approved_by);
        $this->assertNotNull($pendingUser->approved_at);
        $this->assertNull($pendingUser->blocked_at);
    }

    public function test_administrator_can_block_user_and_revoke_tokens(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        $activeUser = $this->createUser(
            UserRole::Manager,
            UserStatus::Active,
        );

        $activeUser->createToken('test-device');

        Sanctum::actingAs($administrator);

        $this->patchJson("/api/admin/users/{$activeUser->id}", [
            'role' => UserRole::Manager->value,
            'status' => UserStatus::Blocked->value,
        ])
            ->assertOk()
            ->assertJsonPath('user.status', UserStatus::Blocked->value);

        $activeUser->refresh();

        $this->assertSame(UserStatus::Blocked, $activeUser->status);
        $this->assertNotNull($activeUser->blocked_at);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $activeUser->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_administrator_cannot_change_own_access(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        Sanctum::actingAs($administrator);

        $this->patchJson("/api/admin/users/{$administrator->id}", [
            'role' => UserRole::Reader->value,
            'status' => UserStatus::Blocked->value,
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Você não pode alterar seu próprio perfil ou status.',
            );

        $administrator->refresh();

        $this->assertSame(
            UserRole::Administrator,
            $administrator->role,
        );

        $this->assertSame(
            UserStatus::Active,
            $administrator->status,
        );
    }

    public function test_administrator_cannot_use_invalid_role_or_status(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        $user = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
        );

        Sanctum::actingAs($administrator);

        $this->patchJson("/api/admin/users/{$user->id}", [
            'role' => 'invalid-role',
            'status' => 'invalid-status',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
                'status',
            ]);
    }

    private function createUser(
        UserRole $role,
        UserStatus $status,
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'status' => $status,
            'approved_at' => $status === UserStatus::Active
                ? now()
                : null,
            'blocked_at' => $status === UserStatus::Blocked
                ? now()
                : null,
        ])->save();

        return $user;
    }
}

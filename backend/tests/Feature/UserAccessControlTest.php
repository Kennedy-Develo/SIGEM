<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_receives_reader_role_and_pending_status(): void
    {
        $user = User::factory()->create();

        $user->refresh();

        $this->assertSame(UserRole::Reader, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertFalse($user->isActive());
        $this->assertFalse($user->isAdministrator());
    }

    public function test_active_administrator_is_identified_correctly(): void
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => UserRole::Administrator,
            'status' => UserStatus::Active,
            'approved_at' => now(),
        ])->save();

        $user->refresh();

        $this->assertTrue($user->isActive());
        $this->assertTrue($user->isAdministrator());
        $this->assertNotNull($user->approved_at);
    }

    public function test_user_approval_relationships_work_correctly(): void
    {
        $administrator = User::factory()->create();

        $administrator->forceFill([
            'role' => UserRole::Administrator,
            'status' => UserStatus::Active,
            'approved_at' => now(),
        ])->save();

        $user = User::factory()->create();

        $user->forceFill([
            'status' => UserStatus::Active,
            'approved_by' => $administrator->id,
            'approved_at' => now(),
        ])->save();

        $user->refresh();
        $administrator->refresh();

        $this->assertTrue($user->approver->is($administrator));
        $this->assertTrue($administrator->approvedUsers->contains($user));
    }
}

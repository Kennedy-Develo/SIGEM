<?php

namespace Tests\Feature\Audit;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_access_update_creates_audit_record(): void
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

        $this
            ->withHeader(
                'User-Agent',
                'SIGEM Audit Test',
            )
            ->patchJson(
                "/api/admin/users/{$pendingUser->id}",
                [
                    'role' => UserRole::Operator->value,
                    'status' => UserStatus::Active->value,
                ],
            )
            ->assertOk();

        $auditLog = AuditLog::query()->sole();

        $this->assertSame(
            $administrator->id,
            $auditLog->actor_id,
        );

        $this->assertSame(
            AuditAction::UserAccessUpdated,
            $auditLog->action,
        );

        $this->assertSame(
            User::class,
            $auditLog->subject_type,
        );

        $this->assertSame(
            $pendingUser->id,
            $auditLog->subject_id,
        );

        $this->assertSame(
            UserRole::Reader->value,
            $auditLog->old_values['role'],
        );

        $this->assertSame(
            UserStatus::Pending->value,
            $auditLog->old_values['status'],
        );

        $this->assertSame(
            UserRole::Operator->value,
            $auditLog->new_values['role'],
        );

        $this->assertSame(
            UserStatus::Active->value,
            $auditLog->new_values['status'],
        );

        $this->assertContains(
            'role',
            $auditLog->metadata['changed_fields'],
        );

        $this->assertContains(
            'status',
            $auditLog->metadata['changed_fields'],
        );

        $this->assertSame(
            $administrator->name,
            $auditLog->metadata['actor_name'],
        );

        $this->assertSame(
            $administrator->email,
            $auditLog->metadata['actor_email'],
        );

        $this->assertSame(
            'SIGEM Audit Test',
            $auditLog->user_agent,
        );

        $this->assertNotNull($auditLog->ip_address);

        $this->assertTrue(
            $auditLog->actor->is($administrator),
        );

        $this->assertTrue(
            $auditLog->subject->is($pendingUser),
        );
    }

    public function test_sensitive_information_is_redacted(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        $user = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
        );

        $auditLog = app(AuditLogger::class)->record(
            action: AuditAction::UserAccessUpdated,
            subject: $user,
            actor: $administrator,
            oldValues: [
                'password' => 'old-secret',
                'profile' => [
                    'token' => 'old-token',
                ],
            ],
            newValues: [
                'password_confirmation' => 'new-secret',
                'profile' => [
                    'remember_token' => 'remember-secret',
                ],
            ],
            metadata: [
                'token' => 'metadata-token',
            ],
        );

        $this->assertSame(
            '[REDACTED]',
            $auditLog->old_values['password'],
        );

        $this->assertSame(
            '[REDACTED]',
            $auditLog->old_values['profile']['token'],
        );

        $this->assertSame(
            '[REDACTED]',
            $auditLog->new_values['password_confirmation'],
        );

        $this->assertSame(
            '[REDACTED]',
            $auditLog->new_values['profile']['remember_token'],
        );

        $this->assertSame(
            '[REDACTED]',
            $auditLog->metadata['token'],
        );
    }

    public function test_audit_record_cannot_be_updated(): void
    {
        $auditLog = $this->createAuditLog();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Registros de auditoria não podem ser alterados.',
        );

        $auditLog->update([
            'ip_address' => '127.0.0.2',
        ]);
    }

    public function test_audit_record_cannot_be_deleted(): void
    {
        $auditLog = $this->createAuditLog();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Registros de auditoria não podem ser removidos.',
        );

        $auditLog->delete();
    }

    private function createAuditLog(): AuditLog
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        $user = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
        );

        return app(AuditLogger::class)->record(
            action: AuditAction::UserAccessUpdated,
            subject: $user,
            actor: $administrator,
            oldValues: [
                'role' => UserRole::Reader->value,
                'status' => UserStatus::Pending->value,
            ],
            newValues: [
                'role' => UserRole::Operator->value,
                'status' => UserStatus::Active->value,
            ],
        );
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

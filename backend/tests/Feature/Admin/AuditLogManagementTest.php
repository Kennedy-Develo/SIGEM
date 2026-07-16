<?php

namespace Tests\Feature\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_audit_history(): void
    {
        $this->getJson('/api/admin/audit-logs')
            ->assertUnauthorized();
    }

    public function test_regular_user_cannot_view_audit_history(): void
    {
        $reader = $this->createUser(
            UserRole::Reader,
            UserStatus::Active,
        );

        Sanctum::actingAs($reader);

        $this->getJson('/api/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_administrator_can_view_audit_history(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
            [
                'name' => 'Administrador do SIGEM',
                'email' => 'administrador@sigem.test',
            ],
        );

        $affectedUser = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
            [
                'name' => 'Usuário Auditado',
                'email' => 'auditado@sigem.test',
            ],
        );

        app(AuditLogger::class)->record(
            action: AuditAction::UserAccessUpdated,
            subject: $affectedUser,
            actor: $administrator,
            oldValues: [
                'role' => UserRole::Reader->value,
                'status' => UserStatus::Pending->value,
            ],
            newValues: [
                'role' => UserRole::Operator->value,
                'status' => UserStatus::Active->value,
            ],
            metadata: [
                'changed_fields' => [
                    'role',
                    'status',
                ],
            ],
        );

        Sanctum::actingAs($administrator);

        $this->getJson('/api/admin/audit-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.action',
                AuditAction::UserAccessUpdated->value,
            )
            ->assertJsonPath(
                'data.0.action_label',
                AuditAction::UserAccessUpdated->label(),
            )
            ->assertJsonPath(
                'data.0.actor.id',
                $administrator->id,
            )
            ->assertJsonPath(
                'data.0.actor.name',
                'Administrador do SIGEM',
            )
            ->assertJsonPath(
                'data.0.subject.id',
                $affectedUser->id,
            )
            ->assertJsonPath(
                'data.0.subject.name',
                'Usuário Auditado',
            )
            ->assertJsonPath(
                'data.0.old_values.role',
                UserRole::Reader->value,
            )
            ->assertJsonPath(
                'data.0.old_values.status',
                UserStatus::Pending->value,
            )
            ->assertJsonPath(
                'data.0.new_values.role',
                UserRole::Operator->value,
            )
            ->assertJsonPath(
                'data.0.new_values.status',
                UserStatus::Active->value,
            )
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'action',
                        'action_label',
                        'actor',
                        'subject',
                        'old_values',
                        'new_values',
                        'metadata',
                        'ip_address',
                        'user_agent',
                        'created_at',
                    ],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_administrator_can_filter_audit_history(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        $firstUser = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
            [
                'name' => 'Usuário Alfa',
                'email' => 'alfa@sigem.test',
            ],
        );

        $secondUser = $this->createUser(
            UserRole::Reader,
            UserStatus::Pending,
            [
                'name' => 'Usuário Beta',
                'email' => 'beta@sigem.test',
            ],
        );

        $logger = app(AuditLogger::class);

        $logger->record(
            action: AuditAction::UserAccessUpdated,
            subject: $firstUser,
            actor: $administrator,
            oldValues: [
                'status' => UserStatus::Pending->value,
            ],
            newValues: [
                'status' => UserStatus::Active->value,
            ],
        );

        $logger->record(
            action: AuditAction::UserAccessUpdated,
            subject: $secondUser,
            actor: $administrator,
            oldValues: [
                'status' => UserStatus::Pending->value,
            ],
            newValues: [
                'status' => UserStatus::Blocked->value,
            ],
        );

        Sanctum::actingAs($administrator);

        $this->getJson(
            '/api/admin/audit-logs?'.
            http_build_query([
                'search' => 'Alfa',
                'action' => AuditAction::UserAccessUpdated->value,
                'actor_id' => $administrator->id,
                'user_id' => $firstUser->id,
                'per_page' => 25,
            ]),
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.subject.id',
                $firstUser->id,
            )
            ->assertJsonPath(
                'data.0.subject.name',
                'Usuário Alfa',
            )
            ->assertJsonPath(
                'per_page',
                25,
            );
    }

    public function test_audit_history_rejects_invalid_filters(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
            UserStatus::Active,
        );

        Sanctum::actingAs($administrator);

        $this->getJson(
            '/api/admin/audit-logs?'.
            http_build_query([
                'action' => 'invalid-action',
                'from' => '2026-07-16',
                'to' => '2026-07-15',
                'per_page' => 10,
            ]),
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'action',
                'to',
                'per_page',
            ]);
    }

    /**
     * Create a user with the requested access settings.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(
        UserRole $role,
        UserStatus $status,
        array $attributes = [],
    ): User {
        $user = User::factory()->create($attributes);

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

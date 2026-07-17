<?php

namespace Tests\Feature\Manifestation;

use App\Enums\AuditAction;
use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Manifestation;
use App\Models\Sector;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SectorSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateManifestationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SectorSeeder::class,
            SubjectSeeder::class,
        ]);
    }

    public function test_authenticated_user_can_view_manifestation_details(): void
    {
        $reader = $this->createUser(UserRole::Reader);
        $creator = $this->createUser(UserRole::Operator);
        $assignee = $this->createUser(UserRole::Operator);

        $manifestation = $this->createManifestation(
            creator: $creator,
            assignee: $assignee,
        );

        $manifestation->assignments()->create([
            'assignee_id' => $assignee->id,
            'assigned_by_id' => $creator->id,
            'assigned_at' => now(),
            'assignment_reason' => 'Distribuição inicial.',
        ]);

        $this
            ->actingAs($reader)
            ->getJson("/api/manifestations/{$manifestation->id}")
            ->assertOk()
            ->assertJsonPath(
                'manifestation.id',
                $manifestation->id,
            )
            ->assertJsonPath(
                'manifestation.nup',
                $manifestation->nup,
            )
            ->assertJsonPath(
                'manifestation.current_assignee.id',
                $assignee->id,
            )
            ->assertJsonCount(
                1,
                'manifestation.assignments',
            );
    }

    public function test_manager_can_update_manifestation_general_data(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $creator = $this->createUser(UserRole::Operator);
        $assignee = $this->createUser(UserRole::Operator);

        $manifestation = $this->createManifestation(
            creator: $creator,
            assignee: $assignee,
        );

        $response = $this
            ->actingAs($manager)
            ->patchJson(
                "/api/manifestations/{$manifestation->id}",
                [
                    'summary' => 'Resumo atualizado pela gestão.',
                    'description' => 'Descrição atualizada durante a edição.',
                    'conclusion_responsible_area' => 'Coordenação responsável pela resposta final.',
                    'current_deadline_at' => '2026-08-20',
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Manifestação atualizada com sucesso.',
            )
            ->assertJsonPath(
                'manifestation.summary',
                'Resumo atualizado pela gestão.',
            )
            ->assertJsonPath(
                'manifestation.conclusion_responsible_area',
                'Coordenação responsável pela resposta final.',
            );

        $this->assertDatabaseHas('manifestations', [
            'id' => $manifestation->id,
            'summary' => 'Resumo atualizado pela gestão.',
            'description' => 'Descrição atualizada durante a edição.',
            'conclusion_responsible_area' => 'Coordenação responsável pela resposta final.',
            'current_deadline_at' => '2026-08-20 00:00:00',
            'updated_by_id' => $manager->id,
        ]);
    }

    public function test_assigned_operator_can_update_manifestation(): void
    {
        $creator = $this->createUser(UserRole::Manager);
        $operator = $this->createUser(UserRole::Operator);

        $manifestation = $this->createManifestation(
            creator: $creator,
            assignee: $operator,
        );

        $this
            ->actingAs($operator)
            ->patchJson(
                "/api/manifestations/{$manifestation->id}",
                [
                    'summary' => 'Resumo atualizado pelo responsável.',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'manifestation.summary',
                'Resumo atualizado pelo responsável.',
            );

        $this->assertDatabaseHas('manifestations', [
            'id' => $manifestation->id,
            'summary' => 'Resumo atualizado pelo responsável.',
            'updated_by_id' => $operator->id,
        ]);
    }

    public function test_unrelated_operator_cannot_view_or_update_manifestation(): void
    {
        $creator = $this->createUser(UserRole::Manager);
        $assignee = $this->createUser(UserRole::Operator);
        $unrelatedOperator = $this->createUser(UserRole::Operator);

        $manifestation = $this->createManifestation(
            creator: $creator,
            assignee: $assignee,
        );

        $this
            ->actingAs($unrelatedOperator)
            ->getJson("/api/manifestations/{$manifestation->id}")
            ->assertForbidden();

        $this
            ->actingAs($unrelatedOperator)
            ->patchJson(
                "/api/manifestations/{$manifestation->id}",
                [
                    'summary' => 'Alteração não autorizada.',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('manifestations', [
            'id' => $manifestation->id,
            'summary' => 'Alteração não autorizada.',
        ]);
    }

    public function test_reader_cannot_update_manifestation(): void
    {
        $reader = $this->createUser(UserRole::Reader);
        $creator = $this->createUser(UserRole::Manager);

        $manifestation = $this->createManifestation(
            creator: $creator,
        );

        $this
            ->actingAs($reader)
            ->patchJson(
                "/api/manifestations/{$manifestation->id}",
                [
                    'summary' => 'Alteração feita pelo leitor.',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('manifestations', [
            'id' => $manifestation->id,
            'summary' => 'Alteração feita pelo leitor.',
        ]);
    }

    public function test_duplicate_nup_is_rejected_during_update(): void
    {
        $manager = $this->createUser(UserRole::Manager);

        $firstManifestation = $this->createManifestation(
            creator: $manager,
            nup: '01217004821202690',
        );

        $secondManifestation = $this->createManifestation(
            creator: $manager,
            nup: '01217004822202634',
        );

        $this
            ->actingAs($manager)
            ->patchJson(
                "/api/manifestations/{$secondManifestation->id}",
                [
                    'nup' => $firstManifestation->nup,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nup');

        $this->assertDatabaseHas('manifestations', [
            'id' => $secondManifestation->id,
            'nup' => '01217004822202634',
        ]);
    }

    public function test_reassignment_preserves_assignment_history(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $firstAssignee = $this->createUser(UserRole::Operator);
        $secondAssignee = $this->createUser(UserRole::Operator);

        $manifestation = $this->createManifestation(
            creator: $manager,
            assignee: $firstAssignee,
        );

        $oldAssignment = $manifestation->assignments()->create([
            'assignee_id' => $firstAssignee->id,
            'assigned_by_id' => $manager->id,
            'assigned_at' => now()->subDay(),
            'assignment_reason' => 'Distribuição inicial.',
        ]);

        $this
            ->actingAs($manager)
            ->patchJson(
                "/api/manifestations/{$manifestation->id}",
                [
                    'current_assignee_id' => $secondAssignee->id,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'manifestation.current_assignee.id',
                $secondAssignee->id,
            );

        $oldAssignment->refresh();

        $this->assertNotNull($oldAssignment->ended_at);
        $this->assertSame(
            $manager->id,
            $oldAssignment->ended_by_id,
        );
        $this->assertSame(
            'Responsável alterado durante a edição.',
            $oldAssignment->ending_reason,
        );

        $this->assertDatabaseHas('manifestation_assignments', [
            'manifestation_id' => $manifestation->id,
            'assignee_id' => $secondAssignee->id,
            'assigned_by_id' => $manager->id,
            'ended_at' => null,
            'assignment_reason' => 'Responsável alterado durante a edição.',
        ]);

        $this->assertDatabaseCount(
            'manifestation_assignments',
            2,
        );
    }

    public function test_manifestation_update_is_recorded_in_audit_log(): void
    {
        $manager = $this->createUser(UserRole::Manager);

        $manifestation = $this->createManifestation(
            creator: $manager,
        );

        $this
            ->actingAs($manager)
            ->patchJson(
                "/api/manifestations/{$manifestation->id}",
                [
                    'summary' => 'Novo resumo auditado.',
                    'conclusion_responsible_area' => 'Área conclusiva atualizada.',
                ],
            )
            ->assertOk();

        $auditLog = AuditLog::query()
            ->where(
                'action',
                AuditAction::ManifestationUpdated->value,
            )
            ->where(
                'subject_type',
                Manifestation::class,
            )
            ->where(
                'subject_id',
                $manifestation->id,
            )
            ->firstOrFail();

        $this->assertSame(
            $manager->id,
            $auditLog->actor_id,
        );

        $this->assertSame(
            'Resumo inicial da manifestação.',
            $auditLog->old_values['summary'],
        );

        $this->assertSame(
            'Novo resumo auditado.',
            $auditLog->new_values['summary'],
        );

        $this->assertSame(
            $manifestation->nup,
            $auditLog->metadata['nup'],
        );

        $this->assertContains(
            'summary',
            $auditLog->metadata['changed_fields'],
        );

        $this->assertContains(
            'conclusion_responsible_area',
            $auditLog->metadata['changed_fields'],
        );
    }

    public function test_archived_manifestation_cannot_be_updated(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
        );

        $manifestation = $this->createManifestation(
            creator: $administrator,
            status: ManifestationStatus::Archived,
        );

        $this
            ->actingAs($administrator)
            ->patchJson(
                "/api/manifestations/{$manifestation->id}",
                [
                    'summary' => 'Tentativa de editar arquivada.',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('manifestation');

        $this->assertDatabaseHas('manifestations', [
            'id' => $manifestation->id,
            'summary' => 'Resumo inicial da manifestação.',
            'status' => ManifestationStatus::Archived->value,
        ]);
    }

    private function createManifestation(
        User $creator,
        ?User $assignee = null,
        string $nup = '01217004821202690',
        ManifestationStatus $status = ManifestationStatus::Registered,
    ): Manifestation {
        $subject = Subject::query()
            ->where('active', true)
            ->whereHas('subsubjects', function ($query): void {
                $query->where('active', true);
            })
            ->firstOrFail();

        $subsubject = $subject
            ->subsubjects()
            ->where('active', true)
            ->firstOrFail();

        $sector = Sector::query()
            ->where('acronym', 'OUVID')
            ->where('active', true)
            ->firstOrFail();

        return Manifestation::query()->create([
            'nup' => $nup,
            'source' => ManifestationSource::FalaBr,
            'type' => ManifestationType::Request,
            'status' => $status,
            'subject_id' => $subject->id,
            'subsubject_id' => $subsubject->id,
            'sector_id' => $sector->id,
            'conclusion_responsible_area' => 'Área responsável inicial.',
            'current_assignee_id' => $assignee?->id,
            'created_by_id' => $creator->id,
            'updated_by_id' => $creator->id,
            'summary' => 'Resumo inicial da manifestação.',
            'description' => 'Descrição inicial da manifestação.',
            'opened_at' => '2026-07-16',
            'original_deadline_at' => '2026-08-15',
            'current_deadline_at' => '2026-08-15',
            'archived_at' => $status === ManifestationStatus::Archived
                ? now()
                : null,
        ]);
    }

    private function createUser(
        UserRole $role,
        UserStatus $status = UserStatus::Active,
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

        return $user->refresh();
    }
}

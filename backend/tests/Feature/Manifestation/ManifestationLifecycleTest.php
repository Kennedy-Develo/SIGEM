<?php

namespace Tests\Feature\Manifestation;

use App\Enums\AuditAction;
use App\Enums\ManifestationLifecycleAction;
use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Manifestation;
use App\Models\Sector;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SectorSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestationLifecycleTest extends TestCase
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

    public function test_manager_can_start_manifestation_service(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Start->value,
            ])
            ->assertOk()
            ->assertJsonPath(
                'manifestation.status',
                ManifestationStatus::InProgress->value,
            );

        $this->assertSame(
            ManifestationStatus::InProgress,
            $manifestation->refresh()->status,
        );
    }

    public function test_manager_can_extend_manifestation_deadline(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation(
            $manager,
            ManifestationStatus::InProgress,
        );

        $newDeadline = now()->addDays(30)->toDateString();

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Extend->value,
                'reason' => 'Necessidade de prazo adicional para análise.',
                'new_deadline_at' => $newDeadline,
            ])
            ->assertOk();

        $manifestation->refresh();

        $this->assertSame(
            $newDeadline,
            $manifestation->current_deadline_at?->toDateString(),
        );

        $this->assertSame(
            'Necessidade de prazo adicional para análise.',
            $manifestation->extension_reason,
        );

        $this->assertNotNull($manifestation->extended_at);
    }

    public function test_manager_can_forward_manifestation_to_another_agency(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation(
            $manager,
            ManifestationStatus::InProgress,
        );

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Forward->value,
                'reason' => 'Demanda pertencente à competência de outro órgão.',
                'external_agency' => 'Ministério da Gestão e da Inovação',
            ])
            ->assertOk();

        $manifestation->refresh();

        $this->assertSame(
            'Ministério da Gestão e da Inovação',
            $manifestation->external_agency,
        );

        $this->assertNotNull(
            $manifestation->forwarded_to_external_agency_at,
        );
    }

    public function test_manager_can_register_ombudsman_response(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation(
            $manager,
            ManifestationStatus::InProgress,
        );

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Answer->value,
            ])
            ->assertOk();

        $this->assertNotNull(
            $manifestation->refresh()->answered_by_ombudsman_at,
        );
    }

    public function test_manager_can_complete_manifestation(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation(
            $manager,
            ManifestationStatus::InProgress,
        );

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Complete->value,
            ])
            ->assertOk()
            ->assertJsonPath(
                'manifestation.status',
                ManifestationStatus::Completed->value,
            );

        $manifestation->refresh();

        $this->assertSame(
            ManifestationStatus::Completed,
            $manifestation->status,
        );

        $this->assertNotNull($manifestation->completed_at);
    }

    public function test_manager_can_archive_completed_manifestation(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation(
            $manager,
            ManifestationStatus::Completed,
        );

        $manifestation->forceFill([
            'completed_at' => now(),
        ])->save();

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Archive->value,
                'reason' => 'Manifestação finalizada e conferida.',
            ])
            ->assertOk()
            ->assertJsonPath(
                'manifestation.status',
                ManifestationStatus::Archived->value,
            );

        $manifestation->refresh();

        $this->assertSame(
            ManifestationStatus::Archived,
            $manifestation->status,
        );

        $this->assertNotNull($manifestation->archived_at);
    }

    public function test_manager_can_reopen_archived_manifestation(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation(
            $manager,
            ManifestationStatus::Archived,
        );

        $manifestation->forceFill([
            'completed_at' => now()->subDay(),
            'archived_at' => now(),
        ])->save();

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Reopen->value,
                'reason' => 'Necessidade de nova análise.',
            ])
            ->assertOk()
            ->assertJsonPath(
                'manifestation.status',
                ManifestationStatus::InProgress->value,
            );

        $manifestation->refresh();

        $this->assertSame(
            ManifestationStatus::InProgress,
            $manifestation->status,
        );

        $this->assertNull($manifestation->completed_at);
        $this->assertNull($manifestation->archived_at);
    }

    public function test_invalid_lifecycle_transition_is_rejected(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Archive->value,
                'reason' => 'Tentativa de arquivamento.',
            ])
            ->assertUnprocessable();

        $this->assertSame(
            ManifestationStatus::Registered,
            $manifestation->refresh()->status,
        );
    }

    public function test_reader_cannot_change_manifestation_lifecycle(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $reader = $this->createUser(UserRole::Reader);
        $manifestation = $this->createManifestation($manager);

        $this->actingAs($reader)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Start->value,
            ])
            ->assertForbidden();
    }

    public function test_lifecycle_change_is_recorded_in_audit_log(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this->actingAs($manager)
            ->postJson($this->transitionUrl($manifestation), [
                'action' => ManifestationLifecycleAction::Start->value,
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ManifestationLifecycleChanged->value,
        ]);
    }

    private function transitionUrl(Manifestation $manifestation): string
    {
        return "/api/manifestations/{$manifestation->id}/transition";
    }

    private function createUser(UserRole $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'blocked_at' => null,
        ])->save();

        return $user->refresh();
    }

    private function createManifestation(
        User $actor,
        ManifestationStatus $status = ManifestationStatus::Registered,
    ): Manifestation {
        $subject = Subject::query()->where('active', true)->firstOrFail();
        $subsubject = $subject->subsubjects()->where('active', true)->firstOrFail();
        $sector = Sector::query()->where('active', true)->firstOrFail();

        return Manifestation::query()->create([
            'nup' => '01217004821202690',
            'source' => ManifestationSource::cases()[0],
            'type' => ManifestationType::cases()[0],
            'status' => $status,
            'subject_id' => $subject->id,
            'subsubject_id' => $subsubject->id,
            'sector_id' => $sector->id,
            'current_assignee_id' => $actor->id,
            'created_by_id' => $actor->id,
            'updated_by_id' => $actor->id,
            'summary' => 'Manifestação criada para testar o ciclo de vida.',
            'opened_at' => now()->subDays(5)->toDateString(),
            'original_deadline_at' => now()->addDays(10)->toDateString(),
            'current_deadline_at' => now()->addDays(10)->toDateString(),
            'conclusion_responsible_area' => 'Ouvidoria',
        ]);
    }
}

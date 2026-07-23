<?php

namespace Tests\Feature\Manifestation;

use App\Enums\AuditAction;
use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Manifestation;
use App\Models\Sector;
use App\Models\Subject;
use App\Models\Subsubject;
use App\Models\User;
use Database\Seeders\SectorSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashManifestationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_send_manifestation_to_trash(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this
            ->actingAs($manager)
            ->deleteJson("/api/manifestations/{$manifestation->id}", [
                'reason' => 'Registro criado apenas para teste.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Manifestação enviada para a lixeira com sucesso.');

        $this->assertSoftDeleted('manifestations', [
            'id' => $manifestation->id,
        ]);
    }

    public function test_trashed_manifestation_disappears_from_normal_list(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this
            ->actingAs($manager)
            ->deleteJson("/api/manifestations/{$manifestation->id}", [
                'reason' => 'Remover da listagem principal.',
            ])
            ->assertOk();

        $this
            ->actingAs($manager)
            ->getJson('/api/manifestations')
            ->assertOk()
            ->assertJsonMissing([
                'id' => $manifestation->id,
            ]);
    }

    public function test_manager_can_restore_trashed_manifestation(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this
            ->actingAs($manager)
            ->deleteJson("/api/manifestations/{$manifestation->id}", [
                'reason' => 'Registro duplicado para teste.',
            ])
            ->assertOk();

        $this
            ->actingAs($manager)
            ->postJson("/api/manifestations/{$manifestation->id}/restore", [
                'reason' => 'Registro deve voltar para acompanhamento.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Manifestação restaurada com sucesso.');

        $this->assertDatabaseHas('manifestations', [
            'id' => $manifestation->id,
            'deleted_at' => null,
        ]);
    }

    public function test_reader_cannot_send_manifestation_to_trash(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $reader = $this->createUser(UserRole::Reader);
        $manifestation = $this->createManifestation($manager);

        $this
            ->actingAs($reader)
            ->deleteJson("/api/manifestations/{$manifestation->id}", [
                'reason' => 'Tentativa sem permissão.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('manifestations', [
            'id' => $manifestation->id,
            'deleted_at' => null,
        ]);
    }

    public function test_guest_cannot_send_manifestation_to_trash(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this
            ->deleteJson("/api/manifestations/{$manifestation->id}", [
                'reason' => 'Tentativa sem login.',
            ])
            ->assertUnauthorized();
    }

    public function test_trash_and_restore_are_recorded_in_audit_log(): void
    {
        $manager = $this->createUser(UserRole::Manager);
        $manifestation = $this->createManifestation($manager);

        $this
            ->actingAs($manager)
            ->deleteJson("/api/manifestations/{$manifestation->id}", [
                'reason' => 'Auditar envio para lixeira.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ManifestationTrashed->value,
            'actor_id' => $manager->id,
            'subject_id' => $manifestation->id,
        ]);

        $this
            ->actingAs($manager)
            ->postJson("/api/manifestations/{$manifestation->id}/restore", [
                'reason' => 'Auditar restauração.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ManifestationRestored->value,
            'actor_id' => $manager->id,
            'subject_id' => $manifestation->id,
        ]);
    }

    private function createManifestation(User $creator): Manifestation
    {
        $this->seed([
            SubjectSeeder::class,
            SectorSeeder::class,
        ]);

        $subject = Subject::query()->firstOrFail();
        $subsubject = Subsubject::query()
            ->where('subject_id', $subject->id)
            ->firstOrFail();
        $sector = Sector::query()->firstOrFail();

        return Manifestation::query()->create([
            'nup' => fake()->unique()->numerify('####################'),
            'source' => ManifestationSource::FalaBr,
            'type' => ManifestationType::Request,
            'status' => ManifestationStatus::Registered,
            'subject_id' => $subject->id,
            'subsubject_id' => $subsubject->id,
            'sector_id' => $sector->id,
            'conclusion_responsible_area' => 'Ouvidoria',
            'current_assignee_id' => $creator->id,
            'created_by_id' => $creator->id,
            'updated_by_id' => $creator->id,
            'summary' => 'Manifestação criada para teste de lixeira.',
            'description' => 'Descrição da manifestação usada nos testes automatizados.',
            'opened_at' => '2026-07-20',
            'original_deadline_at' => '2026-08-20',
            'current_deadline_at' => '2026-08-20',
        ]);
    }

    private function createUser(UserRole $role): User
    {
        $user = User::factory()->create();

        $user
            ->forceFill([
                'role' => $role,
                'status' => UserStatus::Active,
                'approved_at' => now(),
                'blocked_at' => null,
            ])
            ->save();

        return $user->refresh();
    }
}

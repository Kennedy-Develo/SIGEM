<?php

namespace Tests\Feature\Manifestation;

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

class ListTrashedManifestationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SubjectSeeder::class,
            SectorSeeder::class,
        ]);
    }

    public function test_administrator_can_list_trashed_manifestations(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
        );

        $manifestation = $this->createManifestation(
            user: $administrator,
            nup: '01217005181202635',
        );

        $manifestation->delete();

        $this->actingAs($administrator)
            ->getJson('/api/manifestations/trash')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $manifestation->id,
                'nup' => '01217005181202635',
            ]);

        $this->assertSoftDeleted(
            'manifestations',
            [
                'id' => $manifestation->id,
            ],
        );
    }

    public function test_active_manifestations_do_not_appear_in_trash(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
        );

        $trashedManifestation = $this->createManifestation(
            user: $administrator,
            nup: '01217005181202636',
        );

        $activeManifestation = $this->createManifestation(
            user: $administrator,
            nup: '01217005181202637',
        );

        $trashedManifestation->delete();

        $this->actingAs($administrator)
            ->getJson('/api/manifestations/trash')
            ->assertOk()
            ->assertJsonFragment([
                'nup' => $trashedManifestation->nup,
            ])
            ->assertJsonMissing([
                'nup' => $activeManifestation->nup,
            ]);
    }

    public function test_guest_cannot_view_manifestation_trash(): void
    {
        $this->getJson('/api/manifestations/trash')
            ->assertUnauthorized();
    }

    private function createUser(
        UserRole $role,
    ): User {
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
        User $user,
        string $nup,
    ): Manifestation {
        $subject = Subject::query()
            ->whereHas('subsubjects')
            ->firstOrFail();

        $subsubject = Subsubject::query()
            ->where('subject_id', $subject->id)
            ->firstOrFail();

        $sector = Sector::query()->firstOrFail();

        return Manifestation::query()->create([
            'nup' => $nup,
            'source' => ManifestationSource::FalaBr,
            'type' => ManifestationType::Request,
            'status' => ManifestationStatus::Registered,
            'subject_id' => $subject->id,
            'subsubject_id' => $subsubject->id,
            'sector_id' => $sector->id,
            'current_assignee_id' => $user->id,
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
            'summary' => 'Manifestação criada para testar a lixeira.',
            'description' => 'Registro usado pelos testes automatizados.',
            'opened_at' => '2026-07-28',
            'original_deadline_at' => '2026-08-27',
            'current_deadline_at' => '2026-08-27',
        ]);
    }
}

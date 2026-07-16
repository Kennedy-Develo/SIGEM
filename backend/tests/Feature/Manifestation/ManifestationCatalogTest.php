<?php

namespace Tests\Feature\Manifestation;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Sector;
use App\Models\Subject;
use App\Models\Subsubject;
use App\Models\User;
use Database\Seeders\SectorSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestationCatalogTest extends TestCase
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

    public function test_authenticated_user_can_view_manifestation_catalogs(): void
    {
        $user = $this->createUser(
            UserRole::Operator,
            UserStatus::Active,
        );

        $response = $this
            ->actingAs($user)
            ->getJson('/api/manifestations/catalogs');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'sources' => [
                    '*' => [
                        'value',
                        'label',
                    ],
                ],
                'types' => [
                    '*' => [
                        'value',
                        'label',
                    ],
                ],
                'statuses' => [
                    '*' => [
                        'value',
                        'label',
                        'is_final',
                    ],
                ],
                'subjects' => [
                    '*' => [
                        'id',
                        'name',
                        'subsubjects' => [
                            '*' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                ],
                'sectors' => [
                    '*' => [
                        'id',
                        'acronym',
                        'name',
                        'label',
                    ],
                ],
                'assignees' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'role_label',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'value' => 'fala_br',
                'label' => 'FALA.BR',
            ])
            ->assertJsonFragment([
                'value' => 'sei',
                'label' => 'SEI',
            ])
            ->assertJsonFragment([
                'acronym' => 'OUVID',
            ]);
    }

    public function test_catalogs_only_return_active_and_valid_options(): void
    {
        $reader = $this->createUser(
            UserRole::Reader,
            UserStatus::Active,
        );

        $manager = $this->createUser(
            UserRole::Manager,
            UserStatus::Active,
        );

        $blockedOperator = $this->createUser(
            UserRole::Operator,
            UserStatus::Blocked,
        );

        $inactiveSubject = Subject::query()->create([
            'name' => 'Assunto inativo de teste',
            'active' => false,
        ]);

        Subsubject::query()->create([
            'subject_id' => $inactiveSubject->id,
            'name' => 'Subassunto de assunto inativo',
            'active' => true,
        ]);

        $activeSubject = Subject::query()
            ->active()
            ->firstOrFail();

        $inactiveSubsubject = Subsubject::query()->create([
            'subject_id' => $activeSubject->id,
            'name' => 'Subassunto inativo de teste',
            'active' => false,
        ]);

        $inactiveSector = Sector::query()->create([
            'acronym' => 'INAT',
            'name' => 'Setor inativo de teste',
            'active' => false,
        ]);

        $response = $this
            ->actingAs($reader)
            ->getJson('/api/manifestations/catalogs')
            ->assertOk();

        $subjectIds = collect(
            $response->json('subjects'),
        )->pluck('id');

        $subsubjectIds = collect(
            $response->json('subjects'),
        )->flatMap(
            fn (array $subject) => collect(
                $subject['subsubjects'],
            )->pluck('id'),
        );

        $sectorIds = collect(
            $response->json('sectors'),
        )->pluck('id');

        $assigneeIds = collect(
            $response->json('assignees'),
        )->pluck('id');

        $this->assertFalse(
            $subjectIds->contains($inactiveSubject->id),
        );

        $this->assertFalse(
            $subsubjectIds->contains($inactiveSubsubject->id),
        );

        $this->assertFalse(
            $sectorIds->contains($inactiveSector->id),
        );

        $this->assertTrue(
            $assigneeIds->contains($manager->id),
        );

        $this->assertFalse(
            $assigneeIds->contains($reader->id),
        );

        $this->assertFalse(
            $assigneeIds->contains($blockedOperator->id),
        );
    }

    public function test_guest_cannot_view_manifestation_catalogs(): void
    {
        $this
            ->getJson('/api/manifestations/catalogs')
            ->assertUnauthorized();
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

        return $user->refresh();
    }
}

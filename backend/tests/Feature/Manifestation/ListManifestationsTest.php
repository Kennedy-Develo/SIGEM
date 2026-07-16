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
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\SectorSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListManifestationsTest extends TestCase
{
    use RefreshDatabase;

    private int $nupSequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-16 10:00:00');

        $this->seed([
            SectorSeeder::class,
            SubjectSeeder::class,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_administrator_can_view_all_manifestations(): void
    {
        $administrator = $this->createUser(
            UserRole::Administrator,
        );

        $operator = $this->createUser(
            UserRole::Operator,
        );

        $this->createManifestation([
            'current_assignee_id' => $operator->id,
        ]);

        $this->createManifestation([
            'current_assignee_id' => $administrator->id,
        ]);

        $this->createManifestation([
            'status' => ManifestationStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($administrator)
            ->getJson('/api/manifestations');

        $response
            ->assertOk()
            ->assertJsonPath('manifestations.total', 3)
            ->assertJsonCount(3, 'manifestations.data')
            ->assertJsonStructure([
                'manifestations' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'nup',
                            'source',
                            'type',
                            'status',
                            'summary',
                            'opened_at',
                            'current_deadline_at',
                            'subject',
                            'subsubject',
                            'sector',
                            'current_assignee',
                        ],
                    ],
                    'last_page',
                    'per_page',
                    'total',
                ],
                'indicators' => [
                    'overdue',
                    'due_today',
                    'due_next_7_days',
                    'extended',
                    'completed',
                ],
            ]);
    }

    public function test_operator_only_views_assigned_or_created_manifestations(): void
    {
        $operator = $this->createUser(
            UserRole::Operator,
        );

        $otherOperator = $this->createUser(
            UserRole::Operator,
        );

        $assignedToOperator = $this->createManifestation([
            'created_by_id' => $otherOperator->id,
            'updated_by_id' => $otherOperator->id,
            'current_assignee_id' => $operator->id,
        ]);

        $createdByOperator = $this->createManifestation([
            'created_by_id' => $operator->id,
            'updated_by_id' => $operator->id,
            'current_assignee_id' => $otherOperator->id,
        ]);

        $this->createManifestation([
            'created_by_id' => $otherOperator->id,
            'updated_by_id' => $otherOperator->id,
            'current_assignee_id' => $otherOperator->id,
        ]);

        $response = $this
            ->actingAs($operator)
            ->getJson('/api/manifestations');

        $response
            ->assertOk()
            ->assertJsonPath('manifestations.total', 2);

        $returnedIds = collect(
            $response->json('manifestations.data'),
        )
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [
                $assignedToOperator->id,
                $createdByOperator->id,
            ],
            $returnedIds,
        );
    }

    public function test_reader_can_view_all_manifestations(): void
    {
        $reader = $this->createUser(
            UserRole::Reader,
        );

        $operator = $this->createUser(
            UserRole::Operator,
        );

        $this->createManifestation([
            'current_assignee_id' => $operator->id,
        ]);

        $this->createManifestation([
            'created_by_id' => $operator->id,
            'updated_by_id' => $operator->id,
            'current_assignee_id' => $operator->id,
        ]);

        $this
            ->actingAs($reader)
            ->getJson('/api/manifestations')
            ->assertOk()
            ->assertJsonPath('manifestations.total', 2);
    }

    public function test_search_and_filters_return_matching_manifestation(): void
    {
        $manager = $this->createUser(
            UserRole::Manager,
        );

        $matchingManifestation = $this->createManifestation([
            'source' => ManifestationSource::FalaBr,
            'status' => ManifestationStatus::InProgress,
            'summary' => 'Pedido sobre desenvolvimento tecnológico.',
        ]);

        $this->createManifestation([
            'source' => ManifestationSource::Sei,
            'status' => ManifestationStatus::Registered,
            'summary' => 'Documento administrativo interno.',
        ]);

        $query = http_build_query([
            'search' => 'desenvolvimento tecnológico',
            'source' => ManifestationSource::FalaBr->value,
            'status' => ManifestationStatus::InProgress->value,
            'per_page' => 10,
        ]);

        $response = $this
            ->actingAs($manager)
            ->getJson("/api/manifestations?{$query}");

        $response
            ->assertOk()
            ->assertJsonPath('manifestations.total', 1)
            ->assertJsonPath(
                'manifestations.data.0.id',
                $matchingManifestation->id,
            )
            ->assertJsonPath(
                'manifestations.data.0.nup',
                $matchingManifestation->nup,
            );
    }

    public function test_listing_returns_deadline_indicators(): void
    {
        $manager = $this->createUser(
            UserRole::Manager,
        );

        $this->createManifestation([
            'current_deadline_at' => '2026-07-15',
            'extended_at' => '2026-07-10 10:00:00',
        ]);

        $this->createManifestation([
            'current_deadline_at' => '2026-07-16',
        ]);

        $this->createManifestation([
            'current_deadline_at' => '2026-07-19',
        ]);

        $this->createManifestation([
            'status' => ManifestationStatus::Completed,
            'current_deadline_at' => '2026-07-14',
            'completed_at' => '2026-07-14 15:00:00',
        ]);

        $this
            ->actingAs($manager)
            ->getJson('/api/manifestations')
            ->assertOk()
            ->assertJsonPath('indicators.overdue', 1)
            ->assertJsonPath('indicators.due_today', 1)
            ->assertJsonPath('indicators.due_next_7_days', 1)
            ->assertJsonPath('indicators.extended', 1)
            ->assertJsonPath('indicators.completed', 1);
    }

    public function test_guest_cannot_view_manifestations(): void
    {
        $this
            ->getJson('/api/manifestations')
            ->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createManifestation(
        array $attributes = [],
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

        $creator = $this->createUser(
            UserRole::Operator,
        );

        return Manifestation::query()->create(array_merge([
            'nup' => str_pad(
                (string) $this->nupSequence++,
                17,
                '0',
                STR_PAD_LEFT,
            ),
            'source' => ManifestationSource::FalaBr,
            'type' => ManifestationType::Request,
            'status' => ManifestationStatus::Registered,
            'subject_id' => $subject->id,
            'subsubject_id' => $subsubject->id,
            'sector_id' => $sector->id,
            'current_assignee_id' => $creator->id,
            'created_by_id' => $creator->id,
            'updated_by_id' => $creator->id,
            'summary' => 'Manifestação para teste da listagem.',
            'description' => 'Descrição utilizada pelos testes automatizados.',
            'opened_at' => '2026-07-01',
            'original_deadline_at' => '2026-07-20',
            'current_deadline_at' => '2026-07-20',
        ], $attributes));
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
}

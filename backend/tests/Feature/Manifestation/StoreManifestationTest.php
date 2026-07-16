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

class StoreManifestationTest extends TestCase
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

    public function test_operator_can_register_manifestation(): void
    {
        $operator = $this->createUser(
            UserRole::Operator,
            UserStatus::Active,
        );

        $payload = $this->validPayload();

        $response = $this
            ->actingAs($operator)
            ->postJson('/api/manifestations', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Manifestação cadastrada com sucesso.',
            )
            ->assertJsonPath(
                'manifestation.nup',
                '01217004821202690',
            )
            ->assertJsonPath(
                'manifestation.status',
                ManifestationStatus::Registered->value,
            )
            ->assertJsonPath(
                'manifestation.source',
                ManifestationSource::FalaBr->value,
            )
            ->assertJsonPath(
                'manifestation.type',
                ManifestationType::Request->value,
            )
            ->assertJsonPath(
                'manifestation.conclusion_responsible_area',
                $payload['conclusion_responsible_area'],
            );

        $this->assertDatabaseHas('manifestations', [
            'nup' => '01217004821202690',
            'status' => ManifestationStatus::Registered->value,
            'created_by_id' => $operator->id,
            'updated_by_id' => $operator->id,
            'current_assignee_id' => $payload['current_assignee_id'],
            'conclusion_responsible_area' => $payload['conclusion_responsible_area'],
        ]);

        $manifestation = Manifestation::query()
            ->where('nup', '01217004821202690')
            ->firstOrFail();

        $this->assertSame(
            '2026-08-15',
            $manifestation->current_deadline_at?->toDateString(),
        );
    }

    public function test_initial_assignment_is_recorded(): void
    {
        $manager = $this->createUser(
            UserRole::Manager,
            UserStatus::Active,
        );

        $payload = $this->validPayload();

        $response = $this
            ->actingAs($manager)
            ->postJson('/api/manifestations', $payload);

        $response->assertCreated();

        $manifestation = Manifestation::query()
            ->where('nup', '01217004821202690')
            ->firstOrFail();

        $this->assertDatabaseHas('manifestation_assignments', [
            'manifestation_id' => $manifestation->id,
            'assignee_id' => $payload['current_assignee_id'],
            'assigned_by_id' => $manager->id,
            'ended_at' => null,
            'assignment_reason' => 'Distribuição inicial.',
        ]);

        $this->assertCount(1, $manifestation->assignments);
        $this->assertTrue(
            $manifestation->assignments->first()->isCurrent(),
        );
    }

    public function test_duplicate_nup_is_rejected(): void
    {
        $operator = $this->createUser(
            UserRole::Operator,
            UserStatus::Active,
        );

        $payload = $this->validPayload();

        $this
            ->actingAs($operator)
            ->postJson('/api/manifestations', $payload)
            ->assertCreated();

        $this
            ->actingAs($operator)
            ->postJson('/api/manifestations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nup');

        $this->assertDatabaseCount('manifestations', 1);
    }

    public function test_subsubject_must_belong_to_selected_subject(): void
    {
        $operator = $this->createUser(
            UserRole::Operator,
            UserStatus::Active,
        );

        $payload = $this->validPayload();

        $otherSubsubject = Subsubject::query()
            ->where(
                'subject_id',
                '!=',
                $payload['subject_id'],
            )
            ->where('active', true)
            ->firstOrFail();

        $payload['subsubject_id'] = $otherSubsubject->id;

        $this
            ->actingAs($operator)
            ->postJson('/api/manifestations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subsubject_id');

        $this->assertDatabaseCount('manifestations', 0);
    }

    public function test_blocked_user_cannot_be_assignee(): void
    {
        $manager = $this->createUser(
            UserRole::Manager,
            UserStatus::Active,
        );

        $blockedUser = $this->createUser(
            UserRole::Operator,
            UserStatus::Blocked,
        );

        $payload = $this->validPayload();
        $payload['current_assignee_id'] = $blockedUser->id;

        $this
            ->actingAs($manager)
            ->postJson('/api/manifestations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_assignee_id');

        $this->assertDatabaseCount('manifestations', 0);
    }

    public function test_reader_cannot_register_manifestation(): void
    {
        $reader = $this->createUser(
            UserRole::Reader,
            UserStatus::Active,
        );

        $this
            ->actingAs($reader)
            ->postJson(
                '/api/manifestations',
                $this->validPayload(),
            )
            ->assertForbidden();

        $this->assertDatabaseCount('manifestations', 0);
    }

    public function test_guest_cannot_register_manifestation(): void
    {
        $this
            ->postJson(
                '/api/manifestations',
                $this->validPayload(),
            )
            ->assertUnauthorized();

        $this->assertDatabaseCount('manifestations', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
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

        $assignee = $this->createUser(
            UserRole::Operator,
            UserStatus::Active,
        );

        return [
            'nup' => '01217.004821/2026-90',
            'source' => ManifestationSource::FalaBr->value,
            'type' => ManifestationType::Request->value,
            'subject_id' => $subject->id,
            'subsubject_id' => $subsubject->id,
            'sector_id' => $sector->id,
            'conclusion_responsible_area' => 'Coordenação responsável pela elaboração da resposta final.',
            'current_assignee_id' => $assignee->id,
            'summary' => 'Solicitação de informação institucional.',
            'description' => 'Manifestação cadastrada pela API do SIGEM.',
            'opened_at' => '2026-07-16',
            'original_deadline_at' => '2026-08-15',
        ];
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

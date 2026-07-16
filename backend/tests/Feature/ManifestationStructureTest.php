<?php

namespace Tests\Feature;

use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use App\Models\Manifestation;
use App\Models\ManifestationAssignment;
use App\Models\Sector;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\SectorSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestationStructureTest extends TestCase
{
    use RefreshDatabase;

    private int $nupSequence = 0;

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

    public function test_manifestation_stores_catalogs_and_enum_casts(): void
    {
        $manifestation = $this->createManifestation();

        $this->assertInstanceOf(
            ManifestationSource::class,
            $manifestation->source,
        );

        $this->assertInstanceOf(
            ManifestationType::class,
            $manifestation->type,
        );

        $this->assertInstanceOf(
            ManifestationStatus::class,
            $manifestation->status,
        );

        $this->assertSame(
            'Pesquisa e Desenvolvimento',
            $manifestation->subject->name,
        );

        $this->assertSame(
            'Popularização da Ciência',
            $manifestation->subsubject->name,
        );

        $this->assertSame(
            'OUVID',
            $manifestation->sector->acronym,
        );

        $this->assertNotNull($manifestation->creator);
        $this->assertNotNull($manifestation->currentAssignee);
    }

    public function test_deadline_helpers_identify_overdue_and_due_today(): void
    {
        $overdue = $this->createManifestation([
            'current_deadline_at' => '2026-07-15',
        ]);

        $dueToday = $this->createManifestation([
            'current_deadline_at' => '2026-07-16',
        ]);

        $completed = $this->createManifestation([
            'status' => ManifestationStatus::Completed,
            'current_deadline_at' => '2026-07-15',
            'completed_at' => now(),
        ]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($overdue->isDueToday());

        $this->assertFalse($dueToday->isOverdue());
        $this->assertTrue($dueToday->isDueToday());

        $this->assertFalse($completed->isOverdue());
        $this->assertFalse($completed->isDueToday());
    }

    public function test_special_conditions_can_exist_at_the_same_time(): void
    {
        $manifestation = $this->createManifestation([
            'extended_at' => now(),
            'extension_reason' => 'Prazo prorrogado pelo órgão.',
            'forwarded_to_external_agency_at' => now(),
            'external_agency' => 'Outro órgão federal',
            'answered_by_ombudsman_at' => now(),
        ]);

        $this->assertTrue($manifestation->isExtended());

        $this->assertTrue(
            $manifestation->isForwardedToExternalAgency(),
        );

        $this->assertTrue(
            $manifestation->isAnsweredByOmbudsman(),
        );
    }

    public function test_assignment_history_preserves_responsibility(): void
    {
        $manifestation = $this->createManifestation();
        $manager = User::factory()->create();
        $assignee = $manifestation->currentAssignee;

        $assignment = ManifestationAssignment::query()->create([
            'manifestation_id' => $manifestation->id,
            'assignee_id' => $assignee->id,
            'assigned_by_id' => $manager->id,
            'assigned_at' => now(),
            'assignment_reason' => 'Distribuição inicial.',
        ]);

        $this->assertTrue($assignment->isCurrent());

        $this->assertSame(
            $manifestation->id,
            $assignment->manifestation->id,
        );

        $this->assertSame(
            $assignee->id,
            $assignment->assignee->id,
        );

        $this->assertSame(
            $manager->id,
            $assignment->assignedBy->id,
        );

        $this->assertSame(
            1,
            $manifestation->assignments()->count(),
        );
    }

    public function test_open_and_assigned_scopes_return_correct_records(): void
    {
        $assignee = User::factory()->create();

        $this->createManifestation([
            'current_assignee_id' => $assignee->id,
            'status' => ManifestationStatus::InProgress,
        ]);

        $this->createManifestation([
            'current_assignee_id' => $assignee->id,
            'status' => ManifestationStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->createManifestation([
            'status' => ManifestationStatus::InProgress,
        ]);

        $result = Manifestation::query()
            ->open()
            ->assignedTo($assignee)
            ->get();

        $this->assertCount(1, $result);

        $this->assertSame(
            $assignee->id,
            $result->first()->current_assignee_id,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createManifestation(
        array $overrides = [],
    ): Manifestation {
        $subject = Subject::query()
            ->where('name', 'Pesquisa e Desenvolvimento')
            ->firstOrFail();

        $subsubject = $subject->subsubjects()
            ->where('name', 'Popularização da Ciência')
            ->firstOrFail();

        $sector = Sector::query()
            ->where('acronym', 'OUVID')
            ->firstOrFail();

        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $this->nupSequence++;

        return Manifestation::query()->create(array_merge([
            'nup' => sprintf('%017d', $this->nupSequence),
            'source' => ManifestationSource::FalaBr,
            'type' => ManifestationType::Request,
            'status' => ManifestationStatus::Registered,
            'subject_id' => $subject->id,
            'subsubject_id' => $subsubject->id,
            'sector_id' => $sector->id,
            'current_assignee_id' => $assignee->id,
            'created_by_id' => $creator->id,
            'summary' => 'Manifestação criada para teste.',
            'opened_at' => '2026-07-10',
            'original_deadline_at' => '2026-07-30',
            'current_deadline_at' => '2026-07-30',
        ], $overrides));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\Subject;
use App\Models\Subsubject;
use Database\Seeders\SectorSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
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

    public function test_sector_catalog_contains_all_expected_sectors(): void
    {
        $this->assertSame(22, Sector::query()->count());

        $this->assertDatabaseHas('sectors', [
            'acronym' => 'OUVID',
            'name' => 'Ouvidoria',
            'active' => true,
        ]);

        $this->assertDatabaseHas('sectors', [
            'acronym' => 'COPOA',
            'name' => 'Coordenação de Apoio e Assessoramento ao Planejamento, Orçamento e Administração',
            'active' => true,
        ]);

        $this->assertDatabaseHas('sectors', [
            'acronym' => 'CTNBIO',
            'name' => 'Comissão Técnica Nacional de Biossegurança',
            'active' => true,
        ]);
    }

    public function test_subject_catalog_contains_expected_quantities(): void
    {
        $this->assertSame(9, Subject::query()->count());
        $this->assertSame(55, Subsubject::query()->count());

        $this->assertDatabaseHas('subjects', [
            'name' => 'Ciência, Tecnologia e Inovação',
            'active' => true,
        ]);

        $this->assertDatabaseHas('subjects', [
            'name' => 'Transparência e Acesso à Informação',
            'active' => true,
        ]);
    }

    public function test_subsubjects_are_linked_to_the_correct_subject(): void
    {
        $subject = Subject::query()
            ->where('name', 'Ciência, Tecnologia e Inovação')
            ->firstOrFail();

        $this->assertSame(6, $subject->subsubjects()->count());

        $this->assertTrue(
            $subject->subsubjects()
                ->where('name', 'Inteligência Artificial')
                ->exists(),
        );

        $transparencySubject = Subject::query()
            ->where('name', 'Transparência e Acesso à Informação')
            ->firstOrFail();

        $this->assertSame(7, $transparencySubject->subsubjects()->count());

        $this->assertTrue(
            $transparencySubject->subsubjects()
                ->where('name', 'Dados Abertos')
                ->exists(),
        );
    }

    public function test_all_catalog_records_are_active(): void
    {
        $this->assertSame(22, Sector::query()->where('active', true)->count());
        $this->assertSame(9, Subject::query()->where('active', true)->count());
        $this->assertSame(55, Subsubject::query()->where('active', true)->count());
    }

    public function test_seeders_can_run_again_without_duplicating_records(): void
    {
        $this->seed([
            SectorSeeder::class,
            SubjectSeeder::class,
        ]);

        $this->assertSame(22, Sector::query()->count());
        $this->assertSame(9, Subject::query()->count());
        $this->assertSame(55, Subsubject::query()->count());
    }
}

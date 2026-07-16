<?php

namespace Tests\Unit;

use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use PHPUnit\Framework\TestCase;

class ManifestationEnumsTest extends TestCase
{
    public function test_manifestation_sources_are_defined_correctly(): void
    {
        $sources = array_map(
            fn (ManifestationSource $source): string => $source->value,
            ManifestationSource::cases(),
        );

        $this->assertSame([
            'fala_br',
            'sei',
        ], $sources);

        $this->assertSame('FALA.BR', ManifestationSource::FalaBr->label());
        $this->assertSame('SEI', ManifestationSource::Sei->label());
    }

    public function test_manifestation_types_follow_the_ouvidoria_manual(): void
    {
        $types = array_map(
            fn (ManifestationType $type): string => $type->label(),
            ManifestationType::cases(),
        );

        $this->assertSame([
            'Acesso à Informação',
            'Comunicação',
            'Denúncia',
            'Elogio',
            'Reclamação',
            'Simplifique',
            'Solicitação',
            'Sugestão',
        ], $types);
    }

    public function test_manifestation_statuses_are_defined_correctly(): void
    {
        $statuses = array_map(
            fn (ManifestationStatus $status): string => $status->label(),
            ManifestationStatus::cases(),
        );

        $this->assertSame([
            'Cadastrada',
            'Em andamento',
            'Concluída',
            'Arquivada',
        ], $statuses);
    }

    public function test_only_completed_and_archived_statuses_are_final(): void
    {
        $this->assertFalse(ManifestationStatus::Registered->isFinal());
        $this->assertFalse(ManifestationStatus::InProgress->isFinal());
        $this->assertTrue(ManifestationStatus::Completed->isFinal());
        $this->assertTrue(ManifestationStatus::Archived->isFinal());
    }
}

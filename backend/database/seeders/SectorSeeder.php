<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            [
                'acronym' => 'AECI',
                'name' => 'Assessoria Especial de Controle Interno',
            ],
            [
                'acronym' => 'ASCCT',
                'name' => 'Assessoria do Conselho Nacional de Ciência e Tecnologia',
            ],
            [
                'acronym' => 'ASCOM',
                'name' => 'Assessoria Especial de Comunicação Social',
            ],
            [
                'acronym' => 'ASPAD',
                'name' => 'Assessoria de Participação Social e Diversidade',
            ],
            [
                'acronym' => 'ASPAR',
                'name' => 'Assessoria Especial de Assuntos Parlamentares e Federativos',
            ],
            [
                'acronym' => 'ASSIN',
                'name' => 'Assessoria Especial de Assuntos Internacionais',
            ],
            [
                'acronym' => 'CERIM',
                'name' => 'Cerimonial',
            ],
            [
                'acronym' => 'CONCEA',
                'name' => 'Conselho Nacional de Controle de Experimentação Animal',
            ],
            [
                'acronym' => 'CONJUR',
                'name' => 'Consultoria Jurídica',
            ],
            [
                'acronym' => 'CORREG',
                'name' => 'Corregedoria',
            ],
            [
                'acronym' => 'CTNBIO',
                'name' => 'Comissão Técnica Nacional de Biossegurança',
            ],
            [
                'acronym' => 'GM',
                'name' => 'Gabinete da Ministra',
            ],
            [
                'acronym' => 'SCTA',
                'name' => 'Subsecretaria de Ciência e Tecnologia para a Amazônia',
            ],
            [
                'acronym' => 'SEDES',
                'name' => 'Secretaria de Ciência e Tecnologia para o Desenvolvimento Social',
            ],
            [
                'acronym' => 'SEPPE',
                'name' => 'Secretaria de Políticas e Programas Estratégicos',
            ],
            [
                'acronym' => 'SETAD',
                'name' => 'Secretaria de Ciência e Tecnologia para Transformação Digital',
            ],
            [
                'acronym' => 'SETEC',
                'name' => 'Secretaria de Desenvolvimento Tecnológico e Inovação',
            ],
            [
                'acronym' => 'SEXEC',
                'name' => 'Secretaria-Executiva',
            ],
            [
                'acronym' => 'SPEO',
                'name' => 'Subsecretaria de Unidades de Pesquisa e Organizações Sociais',
            ],
            [
                'acronym' => 'SPOA',
                'name' => 'Subsecretaria de Planejamento, Orçamento e Administração',
            ],
            [
                'acronym' => 'COPOA',
                'name' => 'Coordenação de Apoio e Assessoramento ao Planejamento, Orçamento e Administração',
            ],
            [
                'acronym' => 'OUVID',
                'name' => 'Ouvidoria',
            ],
        ];

        foreach ($sectors as $sector) {
            Sector::query()->updateOrCreate(
                ['acronym' => $sector['acronym']],
                [
                    'name' => $sector['name'],
                    'active' => true,
                ],
            );
        }
    }
}

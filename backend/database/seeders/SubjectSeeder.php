<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Subsubject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Ciência, Tecnologia e Inovação' => [
                'Centros e Eventos Científicos',
                'Instituições e Centros Tecnológicos',
                'Outros em Tecnologia e Inovação',
                'Inteligência Artificial',
                'Incentivos à Inovação',
                'TICs – Tecnologia da Informação e Comunicação',
            ],

            'Clima' => [
                'Clima e Sustentabilidade',
                'Geociências e Biodiversidade',
                'Alertas de Desastres Naturais',
                'Outros em Climas e Meio Ambiente',
            ],

            'Conduta Ética e Irregularidade de Servidores' => [
                'Assédio Moral',
                'Assédio Sexual',
                'Racismo e Discriminação',
                'Conduta Ética e Irregularidade de Servidores',
            ],

            'Controle Social' => [
                'Biossegurança em Organismos Geneticamente Modificados (OGM)',
                'Conselhos e Comissões',
                'Controle e Experimento com Animais',
                'Conselho Nacional de Ciência e Tecnologia (CCT)',
                'Outros em Conselhos e Comissões',
                'Suposta Comunicação de Irregularidade Relacionada a Maus-Tratos de Animais',
            ],

            'Gestão de Pessoas' => [
                'Aposentadoria e Pensão',
                'Cadastro e Dados de Pessoal',
                'Quadro Funcional e Distribuição',
                'Recrutamento e Seleção',
                'Remuneração e Benefícios',
                'Dados Financeiros (Contracheque, IRPF etc.)',
                'Desenvolvimento de Pessoas e Qualidade de Vida',
                'Colaboradores Terceirizados',
                'Outros em Recursos Humanos',
                'PGD',
            ],

            'Gestão Pública' => [
                'Gestão Institucional e do Conhecimento',
                'Orçamento e Finanças',
                'Recursos Logísticos',
                'Transferências Voluntárias',
                'Cooperação Institucional e Bens Sensíveis',
                'Fundos Nacionais, Governança e Indicadores',
            ],

            'Pesquisa e Desenvolvimento' => [
                'Bolsa de Iniciação Científica Júnior',
                'Chamamento Público Relacionado à Popularização da Ciência',
                'Bolsas e Educação Científica',
                'Outros em Pesquisa e Desenvolvimento',
                'Popularização da Ciência',
                'Tecnologia Assistiva e Social',
                'Novas Tecnologias e Inovação',
            ],

            'Tecnologia da Informação e Sistemas' => [
                'CADSEI, SEI e Consulta Pública',
                'PiBiogás',
                'PNIPE',
                'SiBBr',
                'Outros em Sistemas de TI',
            ],

            'Transparência e Acesso à Informação' => [
                'Transparência Ativa Institucional',
                'Dados Abertos',
                'Viagens (Diárias e Voos FAB)',
                'Documentos Classificados – Lei nº 12.527/2011',
                'Acesso e Cópia de Processos e Documentos',
                'Lei Geral de Proteção de Dados Pessoais (LGPD) – Lei nº 13.709/2018',
                'Outros em Informações Institucionais',
            ],
        ];

        foreach ($catalog as $subjectName => $subsubjects) {
            $subject = Subject::query()->updateOrCreate(
                ['name' => $subjectName],
                ['active' => true],
            );

            foreach ($subsubjects as $subsubjectName) {
                Subsubject::query()->updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'name' => $subsubjectName,
                    ],
                    [
                        'active' => true,
                    ],
                );
            }
        }
    }
}

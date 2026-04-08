<?php

namespace Src\Content\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Content\Domain\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // EIXO B2B (Crescimento e Negócios)
            [
                'name' => 'Marketing e Vendas para Negócios',
                'slug' => 'marketing-automotivo',
                'description' => 'Estratégias de aquisição, posicionamento e vendas para empresas do setor.',
                'role' => 'B2B_RECRUITING',
                'role_description' => 'Atração de novos parceiros e assinantes (oficinas, lojas) através de educação em marketing.',
                'funnel_stage' => 'MOFU',
                'order' => 1,
            ],
            [
                'name' => 'Gestão de Clientes e CRM',
                'slug' => 'gestao-de-clientes',
                'description' => 'Relacionamento, retenção e experiência do cliente para gerar recorrência.',
                'role' => 'B2B_RETENTION',
                'role_description' => 'Conteúdo focado no sucesso do parceiro e retenção da base B2B.',
                'funnel_stage' => 'BOFU',
                'order' => 2,
            ],

            // EIXO B2C (Utilidade e Tráfego)
            [
                'name' => 'Novidades Automotivas',
                'slug' => 'novidades-automotivas',
                'description' => 'Lançamentos, tendências e novidades do mercado automotivo brasileiro.',
                'role' => 'B2C_UTILITY',
                'role_description' => 'Tráfego de leitores interessados em lançamentos e tendências do setor.',
                'funnel_stage' => 'TOFU',
                'order' => 3,
            ],
            [
                'name' => 'Seguro e Proteção Veicular',
                'slug' => 'seguro-auto',
                'description' => 'Tudo sobre coberturas, franquias e proteção para seu veículo.',
                'role' => 'B2C_UTILITY',
                'role_description' => 'Topo de funil para atrair motoristas interessados em segurança e custos fixos.',
                'funnel_stage' => 'TOFU',
                'order' => 4,
            ],
            [
                'name' => 'IPVA, Impostos e Documentação',
                'slug' => 'ipva-e-licenciamento',
                'description' => 'Calendário, obrigações e burocracia veicular descomplicada.',
                'role' => 'B2C_UTILITY',
                'role_description' => 'Tráfego recorrente de motoristas buscando regularização documental.',
                'funnel_stage' => 'TOFU',
                'order' => 5,
            ],
            [
                'name' => 'Dicas e Curiosidades',
                'slug' => 'dicas-e-curiosidades',
                'description' => 'Informações úteis, guias rápidos e curiosidades do mundo automotivo.',
                'role' => 'B2C_UTILITY',
                'role_description' => 'Engajamento e SEO para termos de busca genéricos e tendências.',
                'funnel_stage' => 'TOFU',
                'order' => 6,
            ],

            // EIXO COMERCIAL (Segmentos / Conversão de Lead)
            [
                'name' => 'Oficinas e Centros Automotivos',
                'slug' => 'oficinas-e-centros-automotivos',
                'description' => 'Manutenção, diagnósticos e serviços mecânicos de confiança.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Funil de lead direto para empresas de mecânica e manutenção preventiva.',
                'funnel_stage' => 'BOFU',
                'order' => 7,
            ],
            [
                'name' => 'Autopeças e Acessórios',
                'slug' => 'autopecas-e-acessorios',
                'description' => 'Guias de compra e instalação de componentes e acessórios.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Conversão para lojas de peças físicas e e-commerce.',
                'funnel_stage' => 'BOFU',
                'order' => 8,
            ],
            [
                'name' => 'Estética Automotiva e Lava-jato',
                'slug' => 'estetica-automotiva-e-lava-jato',
                'description' => 'Cuidados com a aparência, limpeza e valorização do seu veículo.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Conversão para serviços de limpeza, detalhamento e proteção estética.',
                'funnel_stage' => 'BOFU',
                'order' => 9,
            ],
            [
                'name' => 'Funilaria e Pintura',
                'slug' => 'funilaria-e-pintura',
                'description' => 'Reparo de colisão, pintura e acabamento estético.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Funil para serviços pesados de reparação de lataria e pintura.',
                'funnel_stage' => 'BOFU',
                'order' => 10,
            ],
            [
                'name' => 'Autoelétrica e Baterias',
                'slug' => 'autoeletrica-e-eletronica',
                'description' => 'Serviços elétricos, baterias e eletrônica embarcada.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Conversão para troca de baterias e diagnósticos elétricos.',
                'funnel_stage' => 'BOFU',
                'order' => 11,
            ],
            [
                'name' => 'Pneus, Rodas e Alinhamento',
                'slug' => 'pneus-alinhamento-e-balanceamento',
                'description' => 'Tudo sobre pneus, geometria e segurança na rodagem.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Funil para serviços de estabilidade, segurança e venda de pneus.',
                'funnel_stage' => 'BOFU',
                'order' => 12,
            ],
            [
                'name' => 'Ar-condicionado e Climatização',
                'slug' => 'ar-condicionado-automotivo',
                'description' => 'Manutenção e saúde do sistema de ar-condicionado veicular.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Conversão para serviços de higienização e reparo de clima.',
                'funnel_stage' => 'BOFU',
                'order' => 13,
            ],
            [
                'name' => 'Concessionárias e Revendas',
                'slug' => 'concessionarias-e-revendas',
                'description' => 'Oportunidades, lançamentos e dicas para compra de novos e usados.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'Funil de lead para trocas, vendas e anúncios de veículos.',
                'funnel_stage' => 'BOFU',
                'order' => 14,
            ],

            // EIXO EDITORIAL (MOFU — Custo, Diagnóstico, Checklist)
            [
                'name' => 'Manutenção e Revisão Programada',
                'slug' => 'manutencao-e-revisao-programada',
                'description' => 'Custos reais, checklists e orientações sobre revisões e manutenção preventiva.',
                'role' => 'B2C_CONVERSION',
                'role_description' => 'MOFU editorial — converte motoristas buscando custo/checklist em leads para oficinas parceiras.',
                'funnel_stage' => 'MOFU',
                'order' => 15,
            ],

            // EIXO B2B (Operação e Gestão)
            [
                'name' => 'Gestão e Operação Automotiva',
                'slug' => 'gestao-e-operacao-automotiva',
                'description' => 'Gestão de agenda, operação e produtividade para donos de oficinas e lojas.',
                'role' => 'B2B_RETENTION',
                'role_description' => 'Conteúdo B2B para retenção e upsell — fala com o dono do negócio sobre eficiência operacional.',
                'funnel_stage' => 'BOFU',
                'order' => 16,
            ],

            // FALLBACK
            [
                'name' => 'Geral',
                'slug' => 'geral',
                'description' => 'Conteúdo geral sobre o mercado e tendências do setor.',
                'role' => 'HUB',
                'role_description' => 'Categoria central para notícias rápidas e conteúdos diversos sem segmentação.',
                'funnel_stage' => 'TOFU',
                'order' => 17,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'role' => $categoryData['role'],
                    'role_description' => $categoryData['role_description'],
                    'funnel_stage' => $categoryData['funnel_stage'],
                    'order' => $categoryData['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}

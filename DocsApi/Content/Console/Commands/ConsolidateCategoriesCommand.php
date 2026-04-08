<?php

namespace Src\Content\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;

class ConsolidateCategoriesCommand extends Command
{
    protected $signature = 'content:consolidate-categories';

    protected $description = 'Consolidate redundant categories into 11 main categories';

    /**
     * Slug-based mapping: redundant slug => target slug
     */
    private const SLUG_MAPPING = [
        'lancamentos-veiculos' => 'novidades-automotivas',
        'vistoria' => 'dicas',
        'auto-center' => 'dicas',
        'vistoria-veicular' => 'dicas',
        'manutencao-economia' => 'dicas',
        'cooperativas-de-seguro' => 'seguro-auto',
        'seguro-automotivo' => 'seguro-auto',
        'fidelizacao' => 'gestao-de-clientes',
        'introducao-ao-marketing-digital' => 'marketing-automotivo',
        'marketing-digital-no-setor-automotivo' => 'marketing-automotivo',
        'marketing-digital-para-oficinas-mecanicas' => 'marketing-automotivo',
        'primeiros-passos-no-marketing-digital' => 'marketing-automotivo',
        'ipva' => 'ipva-e-licenciamento',
        'impostos' => 'ipva-e-licenciamento',
        'ipva-2025' => 'ipva-e-licenciamento',
    ];

    private const MAIN_CATEGORIES = [
        'gestao-de-clientes' => [
            'name' => 'Gestão de Clientes',
            'description' => 'Relacionamento, retenção e experiência para gerar mais recorrência e indicações.',
        ],
        'marketing-automotivo' => [
            'name' => 'Marketing Automotivo',
            'description' => 'Estratégias de aquisição e posicionamento para lojas, oficinas e serviços automotivos.',
        ],
        'implementacao-de-estrategias-digitais' => [
            'name' => 'Implementação de Estratégias Digitais',
            'description' => 'Plano prático para sair da estratégia e chegar na execução de campanhas digitais.',
        ],
        'analise-e-metricas' => [
            'name' => 'Análise e Métricas',
            'description' => 'KPIs, funil e indicadores para acompanhar crescimento com previsibilidade.',
        ],
        'conversao-e-vendas-online' => [
            'name' => 'Conversão e Vendas Online',
            'description' => 'Táticas para transformar visitas em contatos qualificados e vendas.',
        ],
        'otimizacao-de-motores-de-busca-seo' => [
            'name' => 'Otimização de Motores de Busca (SEO)',
            'description' => 'Boas práticas para ranquear melhor no Google e capturar demanda orgânica.',
        ],
        'tendencias-de-marketing-digital' => [
            'name' => 'Tendências de Marketing Digital',
            'description' => 'Mudanças de comportamento, canais e formatos para manter competitividade.',
        ],
        'presenca-online' => [
            'name' => 'Presença Online',
            'description' => 'Site, perfil da empresa e consistência da marca nos canais digitais.',
        ],
        'seguro-auto' => [
            'name' => 'Seguro Auto',
            'description' => 'Coberturas, franquias e comparativos para reduzir risco e custo total.',
        ],
        'ipva-e-licenciamento' => [
            'name' => 'IPVA e Licenciamento',
            'description' => 'Calendário, obrigações e documentação para manter o veículo regularizado.',
        ],
        'dicas' => [
            'name' => 'Dicas',
            'description' => 'Dicas práticas para o dia a dia do setor automotivo.',
        ],
        'novidades-automotivas' => [
            'name' => 'Novidades Automotivas',
            'description' => 'Lançamentos, tendências e novidades do mercado automotivo.',
        ],
        'geral' => [
            'name' => 'Geral',
            'description' => 'Artigos gerais sobre o universo automotivo.',
        ],
    ];

    public function handle(): int
    {
        $this->info('Starting category consolidation...');

        DB::beginTransaction();

        try {
            // 1. Ensure all 11 main categories exist with correct descriptions
            foreach (self::MAIN_CATEGORIES as $slug => $data) {
                $category = Category::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'is_active' => true,
                    ]
                );
                $category->update([
                    'description' => $data['description'],
                    'is_active' => true,
                ]);
                $this->line("  ✓ {$data['name']} (ID: {$category->id})");
            }

            $this->newLine();
            $this->info('Migrating articles from redundant categories...');

            // 2. Migrate articles from redundant → target categories
            foreach (self::SLUG_MAPPING as $oldSlug => $targetSlug) {
                $oldCategory = Category::where('slug', $oldSlug)->first();
                $targetCategory = Category::where('slug', $targetSlug)->first();

                if (! $oldCategory) {
                    $this->warn("  Slug '{$oldSlug}' not found, skipping.");

                    continue;
                }

                if (! $targetCategory) {
                    $this->error("  Target slug '{$targetSlug}' not found!");

                    continue;
                }

                // Get articles from pivot
                $articleIds = DB::table('article_category')
                    ->where('category_id', $oldCategory->id)
                    ->pluck('article_id')
                    ->toArray();

                $count = count($articleIds);

                if ($count > 0) {
                    foreach ($articleIds as $articleId) {
                        $exists = DB::table('article_category')
                            ->where('article_id', $articleId)
                            ->where('category_id', $targetCategory->id)
                            ->exists();

                        if (! $exists) {
                            DB::table('article_category')->insert([
                                'article_id' => $articleId,
                                'category_id' => $targetCategory->id,
                            ]);
                        }
                    }

                    // Remove old pivot entries
                    DB::table('article_category')
                        ->where('category_id', $oldCategory->id)
                        ->delete();
                }

                // Update direct FK
                Article::where('category_id', $oldCategory->id)
                    ->update(['category_id' => $targetCategory->id]);

                // Deactivate redundant category
                $oldCategory->update(['is_active' => false]);

                $this->info("  '{$oldCategory->name}' ({$count} artigos) → '{$targetCategory->name}' ✓ Desativada.");
            }

            DB::commit();

            // Summary
            $this->newLine();
            $activeCount = Category::where('is_active', true)->count();
            $this->info("Consolidation complete! Active categories: {$activeCount}");
            $this->newLine();

            Category::where('is_active', true)->orderBy('id')->get()
                ->each(function ($c) {
                    $articleCount = DB::table('article_category')->where('category_id', $c->id)->count();
                    $this->line("  ✓ {$c->name} (ID: {$c->id}, {$articleCount} artigos)");
                });

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Consolidation failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}

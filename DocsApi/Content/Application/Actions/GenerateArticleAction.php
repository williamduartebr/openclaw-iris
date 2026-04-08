<?php

namespace Src\Content\Application\Actions;

use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;
use Src\Content\Domain\Services\ContentGenerationService;

class GenerateArticleAction
{
    public function __construct(
        private readonly ContentGenerationService $generationService
    ) {}

    public function execute(string $categorySlug, array $titleData, string $provider = 'claude'): Article
    {
        $articleData = $this->generationService->generate($titleData, $provider);

        if (empty($articleData)) {
            throw new \RuntimeException("Falha ao gerar conteúdo para o artigo: {$titleData['title']}");
        }

        $category = Category::where('slug', $categorySlug)->first();

        if (! $category) {
            throw new \RuntimeException("Categoria '$categorySlug' não encontrada.");
        }

        return Article::firstOrCreate(
            ['slug' => $articleData['slug']],
            [
                'category_id' => $category->id,
                'title' => $articleData['title'],
                'excerpt' => $articleData['excerpt'],
                'content' => $articleData['content'],
                'featured_image' => $articleData['featured_image'] ?? $this->getDefaultImage($categorySlug),
                'author_name' => 'Equipe Editorial',
                'reading_time' => $articleData['reading_time'] ?? 8,
                'is_published' => true,
                'published_at' => now(),
                'meta' => $articleData['meta'] ?? [],
            ]
        );
    }

    private function getDefaultImage(string $category): string
    {
        $images = [
            'oficinas-e-centros-automotivos' => 'https://images.unsplash.com/photo-1487754180451-c456f719a1fc?w=1200&h=675&fit=crop',
            'pneus-alinhamento-e-balanceamento' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&h=675&fit=crop',
            'autoeletrica-e-eletronica' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=1200&h=675&fit=crop',
            'ar-condicionado-automotivo' => 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=1200&h=675&fit=crop',
            'autopecas-e-acessorios' => 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=1200&h=675&fit=crop',
            'estetica-automotiva-e-lava-jato' => 'https://images.unsplash.com/photo-1607860108855-64acf2078ed9?w=1200&h=675&fit=crop',
            'funilaria-e-pintura' => 'https://images.unsplash.com/photo-1504222490345-c075b7b6f9ec?w=1200&h=675&fit=crop',
            'seguro-auto' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=1200&h=675&fit=crop',
            'ipva-e-licenciamento' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?w=1200&h=675&fit=crop',
            'novidades-automotivas' => 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=1200&h=675&fit=crop',
            'dicas-e-curiosidades' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=1200&h=675&fit=crop',
            'concessionarias-e-revendas' => 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=1200&h=675&fit=crop',
            'marketing-automotivo' => 'https://images.unsplash.com/photo-1533750349088-cd871a92f312?w=1200&h=675&fit=crop',
            'gestao-de-clientes' => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=1200&h=675&fit=crop',
            'geral' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1200&h=675&fit=crop',
        ];

        return $images[$category] ?? $images['geral'];
    }
}

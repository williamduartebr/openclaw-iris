<?php

namespace Src\Content\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;

class MigrateWordPressArticlesCommand extends Command
{
    protected $signature = 'content:migrate-wp {--file= : Nome do arquivo JSON dentro de database/import-csv-redirect-301/ (padrão: blog_export.json)}';

    protected $description = 'Migrar artigos do WordPress para o domínio local';

    private $redirectsPath;

    private $redirects = [];

    public function handle(): int
    {
        $this->info('Iniciando migração de artigos do WordPress...');

        $this->redirectsPath = base_path('database/import-csv-redirect-301/blog_redirects.json');
        if (! file_exists(dirname($this->redirectsPath))) {
            mkdir(dirname($this->redirectsPath), 0755, true);
        }

        // Read local JSON file
        $fileName = $this->option('file') ?: 'blog_export.json';
        $jsonPath = base_path('database/import-csv-redirect-301/'.$fileName);
        if (! file_exists($jsonPath)) {
            $this->error("Arquivo JSON extraído não encontrado em: {$jsonPath}");

            return self::FAILURE;
        }

        $posts = json_decode(file_get_contents($jsonPath), true);
        if (! $posts || ! is_array($posts)) {
            $this->error("Formato JSON inválido em {$jsonPath}");

            return self::FAILURE;
        }

        $this->info('Foram encontrados '.count($posts).' posts.');

        try {
            // We are turning off truncation on subsequent runs since we use wp_post_id updateOrCreate
            $this->info('Ignorando truncamento para permitir updateOrCreate seguro via wp_post_id.');
        } catch (\Exception $e) {
            $this->warn('Não foi possível configurar as restrições do banco: '.$e->getMessage());
        }

        $bar = $this->output->createProgressBar(count($posts));
        $bar->start();

        foreach ($posts as $post) {
            $this->processPost($post);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        file_put_contents($this->redirectsPath, json_encode($this->redirects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Migração concluída com sucesso.');
        $this->info("JSON de redirects gerado em: {$this->redirectsPath}");

        return self::SUCCESS;
    }

    /**
     * Slug mapping: WordPress category slug => consolidated local slug
     */
    private const CATEGORY_SLUG_MAP = [
        // → novidades-automotivas
        'lancamentos-veiculos' => 'novidades-automotivas',
        // → dicas
        'vistoria' => 'dicas-e-curiosidades',
        'auto-center' => 'dicas-e-curiosidades',
        'vistoria-veicular' => 'dicas-e-curiosidades',
        'manutencao-economia' => 'dicas-e-curiosidades',
        // → seguro-auto
        'cooperativas-de-seguro' => 'seguro-auto',
        'seguro-automotivo' => 'seguro-auto',
        // → gestao-de-clientes
        'fidelizacao' => 'gestao-de-clientes',
        // → marketing-automotivo
        'introducao-ao-marketing-digital' => 'marketing-automotivo',
        'marketing-digital-no-setor-automotivo' => 'marketing-automotivo',
        'marketing-digital-para-oficinas-mecanicas' => 'marketing-automotivo',
        'primeiros-passos-no-marketing-digital' => 'marketing-automotivo',
        // → ipva-e-licenciamento
        'ipva' => 'ipva-e-licenciamento',
        'impostos' => 'ipva-e-licenciamento',
        'ipva-2025' => 'ipva-e-licenciamento',
    ];

    /**
     * Per-post overrides: wp_post_id => local category slug
     * Used to reclassify posts that were tagged as "Geral" in WordPress
     * but belong to a more specific category.
     */
    private const POST_CATEGORY_OVERRIDES = [
        // Novidades Automotivas (lançamentos mensais)
        14275 => 'novidades-automotivas', // Novidades Automotivas de Junho 2025
        14252 => 'novidades-automotivas', // Novidades Automotivas de Maio 2025

        // Oficinas e Centros Automotivos
        14141 => 'oficinas-e-centros-automotivos', // Auto Center vs Oficina Mecânica
        14125 => 'oficinas-e-centros-automotivos', // Tudo Sobre Auto Center

        // Vistoria Cautelar → Dicas e Curiosidades
        14196 => 'dicas-e-curiosidades', // Onde fazer Vistoria Cautelar

        // Marketing Digital / Negócios Automotivos
        12064 => 'marketing-automotivo', // Análise de Desempenho: Estratégias Digitais
        13538 => 'marketing-automotivo', // Como Criar um Perfil Perfeito no MercadoVeiculos
        13518 => 'marketing-automotivo', // Histórias Reais de Sucesso: Negócios Automotivos
        13503 => 'marketing-automotivo', // 5 Estratégias para Turbinar a Visibilidade
        13509 => 'marketing-automotivo', // 7 Erros Críticos que Minam o Sucesso Digital
        13498 => 'marketing-automotivo', // 5 Razões para Digitalizar seu Negócio Automotivo
        13500 => 'marketing-automotivo', // Benefícios da Presença Digital para Negócio Automotivo
        12902 => 'marketing-automotivo', // Oficina de Motos com Estratégias Digitais
        12021 => 'marketing-automotivo', // Maximizando a Conversão: Táticas de Marketing Digital
        11991 => 'marketing-automotivo', // Como Implementar SEO para Negócio Automotivo
    ];

    /**
     * Main categories with descriptions (created if not exists)
     */
    private const MAIN_CATEGORIES = [
        'marketing-automotivo' => [
            'name' => 'Marketing e Vendas para Negócios',
            'description' => 'Estratégias de aquisição, posicionamento e vendas para empresas do setor.',
        ],
        'gestao-de-clientes' => [
            'name' => 'Gestão de Clientes e CRM',
            'description' => 'Relacionamento, retenção e experiência do cliente para gerar recorrência.',
        ],
        'seguro-auto' => [
            'name' => 'Seguro e Proteção Veicular',
            'description' => 'Tudo sobre coberturas, franquias e proteção para seu veículo.',
        ],
        'ipva-e-licenciamento' => [
            'name' => 'IPVA, Impostos e Documentação',
            'description' => 'Calendário, obrigações e burocracia veicular descomplicada.',
        ],
        'dicas-e-curiosidades' => [
            'name' => 'Dicas e Curiosidades',
            'description' => 'Informações úteis, guias rápidos e curiosidades do mundo automotivo.',
        ],
        'geral' => [
            'name' => 'Geral',
            'description' => 'Conteúdo geral sobre o mercado e tendências do setor.',
        ],
        'oficinas-e-centros-automotivos' => [
            'name' => 'Oficinas e Centros Automotivos',
            'description' => 'Manutenção, diagnósticos e serviços mecânicos de confiança.',
        ],
        'autopecas-e-acessorios' => [
            'name' => 'Autopeças e Acessórios',
            'description' => 'Guias de compra e instalação de componentes e acessórios.',
        ],
        'estetica-automotiva-e-lava-jato' => [
            'name' => 'Estética Automotiva e Lava-jato',
            'description' => 'Cuidados com a aparência, limpeza e valorização do seu veículo.',
        ],
        'funilaria-e-pintura' => [
            'name' => 'Funilaria e Pintura',
            'description' => 'Reparo de colisão, pintura e acabamento estético.',
        ],
        'autoeletrica-e-eletronica' => [
            'name' => 'Autoelétrica e Baterias',
            'description' => 'Serviços elétricos, baterias e eletrônica embarcada.',
        ],
        'pneus-alinhamento-e-balanceamento' => [
            'name' => 'Pneus, Rodas e Alinhamento',
            'description' => 'Tudo sobre pneus, geometria e segurança na rodagem.',
        ],
        'ar-condicionado-automotivo' => [
            'name' => 'Ar-condicionado e Climatização',
            'description' => 'Manutenção e saúde do sistema de ar-condicionado veicular.',
        ],
        'concessionarias-e-revendas' => [
            'name' => 'Concessionárias e Revendas',
            'description' => 'Oportunidades, lançamentos e dicas para compra de novos e usados.',
        ],
        'novidades-automotivas' => [
            'name' => 'Novidades Automotivas',
            'description' => 'Lançamentos, tendências e novidades do mercado automotivo.',
        ],
    ];

    private function resolveCategory(string $wpSlug, string $wpName): Category
    {
        // Map WordPress slug to consolidated slug
        $resolvedSlug = self::CATEGORY_SLUG_MAP[$wpSlug] ?? $wpSlug;

        // If it's a known main category, use its defined name/description
        if (isset(self::MAIN_CATEGORIES[$resolvedSlug])) {
            $data = self::MAIN_CATEGORIES[$resolvedSlug];

            return Category::firstOrCreate(
                ['slug' => $resolvedSlug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }

        // Unknown slug — create as-is (fallback)
        return Category::firstOrCreate(
            ['slug' => $resolvedSlug],
            ['name' => $wpName, 'is_active' => true]
        );
    }

    private function processPost(array $post): void
    {
        // Category mapping from JSON
        $categories = $post['categories'] ?? [];
        $primaryCategory = $categories[0] ?? null;

        $categoryId = null;
        $categorySlug = 'geral';

        if (isset(self::POST_CATEGORY_OVERRIDES[$post['post_id']])) {
            $overrideSlug = self::POST_CATEGORY_OVERRIDES[$post['post_id']];
            $localCategory = $this->resolveCategory($overrideSlug, $overrideSlug);
            $categoryId = $localCategory->id;
            $categorySlug = $localCategory->slug;
        } elseif ($primaryCategory) {
            $localCategory = $this->resolveCategory($primaryCategory['slug'], $primaryCategory['name']);
            $categoryId = $localCategory->id;
            $categorySlug = $localCategory->slug;
        } else {
            $localFallback = Category::first();
            $categoryId = $localFallback?->id;
        }

        $oldUrl = "https://mercadoveiculos.com/blog/{$post['slug']}";

        $newUrl = "https://mercadoveiculos.com/{$post['slug']}";

        if ($post['slug'] === 'como-criar-um-perfil-perfeito') {
            $this->info("Pulando post: {$post['slug']} e redirecionando para /anuncie");
            $this->redirects[$oldUrl] = 'https://mercadoveiculos.com/anuncie';

            return;
        }

        $this->redirects[$oldUrl] = $newUrl;

        // Image handling
        $featuredImagePath = null;
        if (! empty($post['featured_image'])) {
            $featuredImagePath = $this->downloadAndUploadImage($post['featured_image'], 'content/featured');
        }

        $title = html_entity_decode($post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $content = $post['content'];
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Remove editorial byline e títulos repetidos ANTES da conversão
        $content = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $content); // Remove H1 (título repetido)
        $bylinePattern = '/\s*<[^>]*>?\s*Por:\s*Equipe Editorial.*?(?:<\/[^>]+>|\n|$)/iu';
        $content = preg_replace($bylinePattern, '', $content);

        $content = $this->parseAndUploadInlineImages($content);

        // --- CONVERSÃO PARA MARKDOWN PROTEGENDO TABELAS ---
        // 1. Extrair e converter tabelas para DIVs para evitar strip_tags
        $tablePlaceholders = [];
        $content = preg_replace_callback('/(?:<figure[^>]*>)?\s*<table[^>]*>.*?<\/table>\s*(?:<\/figure>)?/is', function ($matches) use (&$tablePlaceholders) {
            $index = count($tablePlaceholders);
            $tablePlaceholders[$index] = $this->convertTableToDiv($matches[0]);

            return "MIGRATEDTABLEBLOCK{$index}";
        }, $content);

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'remove_nodes' => 'script style iframe h1',
            'hard_break' => false,
        ]);
        $converter->getEnvironment()->addConverter(new TableConverter);

        $contentMarkdown = $converter->convert($content);

        // 2. Restaurar as DIVs das tabelas no Markdown
        foreach ($tablePlaceholders as $index => $divHtml) {
            $contentMarkdown = str_replace("MIGRATEDTABLEBLOCK{$index}", "\n\n".$divHtml."\n\n", $contentMarkdown);
        }

        // Garantir quebras de linha duplas entre blocos de markdown que o converter pode ter colado
        $contentMarkdown = preg_replace('/(\n#{1,6}\s)/', "\n\n$1", $contentMarkdown);
        $contentMarkdown = preg_replace('/(\n\s*\d+\.\s)/', "\n\n$1", $contentMarkdown); // listas numeradas
        $contentMarkdown = preg_replace('/(\n\s*-\s)/', "\n\n$1", $contentMarkdown);   // listas com traço
        $contentMarkdown = preg_replace('/(\!\[.*?\]\(.*?\))/', "\n\n$1\n\n", $contentMarkdown); // imagens
        $contentMarkdown = preg_replace('/\n{3,}/', "\n\n", $contentMarkdown); // remover excesso de quebras

        // --- PADRONIZAÇÃO DE FAQ ---
        // Transformar títulos que parecem perguntas em '### FAQ: ...'
        // Busca por títulos (## ou ###) que terminam com '?'
        $contentMarkdown = preg_replace('/^(#{2,3})\s*(.*\?)$/m', '### FAQ: $2', $contentMarkdown);

        // Replace internal navigation links no markdown
        $contentMarkdown = str_replace('https://mercadoveiculos.com/blog/', 'https://mercadoveiculos.com/', $contentMarkdown);
        $contentMarkdown = str_replace('https://lp.mercadoveiculos.com/', 'https://mercadoveiculos.com/anuncie/', $contentMarkdown);
        $contentMarkdown = str_replace('utm_source=blog', 'utm_source=artigos', $contentMarkdown);

        $excerpt = strip_tags($post['excerpt'] ?? '');
        $excerpt = html_entity_decode($excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $excerpt = str_replace('[&hellip;]', '...', $excerpt);

        $article = Article::updateOrCreate(
            ['wp_post_id' => $post['post_id']],
            [
                'title' => $title,
                'slug' => $post['slug'],
                'category_id' => $categoryId,
                'excerpt' => $excerpt,
                'content' => $contentMarkdown,
                'featured_image' => $featuredImagePath,
                'is_published' => true,
                'published_at' => \Carbon\Carbon::parse($post['date'])->toDateTimeString(),
                'author_name' => 'Equipe Editorial Mercado Veículos',
            ]
        );

        // Sync all categories (resolved to consolidated slugs)
        $categoryIds = [];
        foreach ($post['categories'] as $cat) {
            $localCat = $this->resolveCategory($cat['slug'], $cat['name']);
            $categoryIds[] = $localCat->id;
        }

        if (! empty($categoryIds)) {
            $article->categories()->sync($categoryIds);
        } elseif ($categoryId) {
            $article->categories()->sync([$categoryId]);
        }
    }

    private function downloadAndUploadImage(string $imageUrl, string $folder = 'content'): ?string
    {
        try {
            $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
            $path = rtrim($folder, '/').'/'.$filename;
            $disk = Storage::disk('s3');

            // Skip upload if file already exists on S3
            if ($disk->exists($path)) {
                return $path;
            }

            $response = Http::timeout(10)->get($imageUrl);

            if (! $response->successful()) {
                $this->warn("Falha ao baixar a imagem de {$imageUrl}");

                return null;
            }

            $imageContent = $response->body();

            try {
                // Do not use public visibility, S3 buckets with ACLs disabled will reject this
                $uploaded = $disk->put($path, $imageContent);
                if (! $uploaded) {
                    throw new \Exception('Storage::put retornou false');
                }
            } catch (\Exception $uploadException) {
                $this->error("Erro de upload para {$filename}: ".$uploadException->getMessage());

                return null;
            }

            $this->info("Imagem enviada com sucesso para o S3: {$path}");

            return $path;
        } catch (\Exception $e) {
            $this->error("Falha ao processar imagem: {$imageUrl}. Motivo: ".$e->getMessage());

            return null;
        }
    }

    private function parseAndUploadInlineImages(string $html): string
    {
        if (empty(trim($html))) {
            return $html;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        // Load with UTF-8 encoding workaround for DOMDocument
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $images = $dom->getElementsByTagName('img');

        $nodesToRemove = [];

        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            // Only upload if it's an external url from the WordPress blog and not already processed
            if (filter_var($src, FILTER_VALIDATE_URL) && str_contains($src, 'wp-content')) {
                // Determine image name and download into an inline subfolder
                $newS3Path = $this->downloadAndUploadImage($src, 'content/inline');
                if ($newS3Path) {
                    $s3Url = Storage::disk('s3')->url($newS3Path);
                    $img->setAttribute('src', $s3Url);

                    // Cleanup srcset if it exists since it points to old URLs
                    if ($img->hasAttribute('srcset')) {
                        $img->removeAttribute('srcset');
                    }
                    if ($img->hasAttribute('sizes')) {
                        $img->removeAttribute('sizes');
                    }

                    // Check if parent node is an anchor tag pointing to the old image
                    $parent = $img->parentNode;
                    if ($parent && strtolower($parent->nodeName) === 'a') {
                        $href = $parent->getAttribute('href');
                        if (str_contains($href, 'wp-content') || str_contains($href, '.jpg') || str_contains($href, '.png') || str_contains($href, '.webp')) {
                            $parent->setAttribute('href', $s3Url);
                        }
                    }
                } else {
                    // Download failed — remove img (and parent <a> wrapper) to avoid broken images
                    $parent = $img->parentNode;
                    if ($parent && strtolower($parent->nodeName) === 'a') {
                        $nodesToRemove[] = $parent;
                    } else {
                        $nodesToRemove[] = $img;
                    }
                }
            }
        }

        // Remove broken image nodes after iteration
        foreach ($nodesToRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        $newHtml = $dom->saveHTML();
        $newHtml = str_replace('<?xml encoding="utf-8" ?>', '', $newHtml);

        $newHtml = html_entity_decode($newHtml, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return trim($newHtml);
    }

    private function convertTableToDiv(string $tableHtml): string
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>'.$tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        if ($loaded === false) {
            return $tableHtml;
        }

        $table = $dom->getElementsByTagName('table')->item(0);
        if (! $table instanceof \DOMElement) {
            return $tableHtml;
        }

        $div = $dom->createElement('div');
        $div->setAttribute('class', 'wp-migrated-table-wrapper my-8 w-full border border-slate-200 rounded-lg overflow-hidden');

        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $tr) {
            if ($tr instanceof \DOMElement) {
                $rows[] = $tr;
            }
        }

        if ($rows === []) {
            return $tableHtml;
        }

        $bodyRowIndex = 0;

        foreach ($rows as $rowIndex => $tr) {
            $cells = [];

            foreach ($tr->childNodes as $childNode) {
                if (! $childNode instanceof \DOMElement) {
                    continue;
                }

                $tagName = strtolower($childNode->tagName);
                if ($tagName === 'th' || $tagName === 'td') {
                    $cells[] = $childNode;
                }
            }

            if ($cells === []) {
                continue;
            }

            $isHeaderRow = $this->isTableHeaderRow($tr, $cells, $rowIndex);
            $row = $dom->createElement('div');

            if ($isHeaderRow) {
                $row->setAttribute('class', 'flex flex-row font-bold bg-slate-100 border-b border-slate-200');
            } else {
                $bgClass = $bodyRowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50';
                $row->setAttribute('class', "flex flex-row border-b border-slate-200 last:border-0 {$bgClass}");
                $bodyRowIndex++;
            }

            foreach ($cells as $cell) {
                $column = $dom->createElement('div');
                $column->setAttribute(
                    'class',
                    $isHeaderRow
                        ? 'flex-1 p-3 md:p-4 text-sm md:text-base border-r border-slate-200 last:border-0'
                        : 'flex-1 p-3 md:p-4 text-sm md:text-base text-gray-800 border-r border-slate-200 last:border-0'
                );

                $text = preg_replace('/\s+/u', ' ', trim($cell->textContent));
                $column->appendChild($dom->createTextNode($text ?: ''));
                $row->appendChild($column);
            }

            $div->appendChild($row);
        }

        $newHtml = $dom->saveHTML($div);

        return $newHtml ?: '';
    }

    /**
     * @param  array<int, \DOMElement>  $cells
     */
    private function isTableHeaderRow(\DOMElement $row, array $cells, int $rowIndex): bool
    {
        if ($row->parentNode instanceof \DOMElement && strtolower($row->parentNode->tagName) === 'thead') {
            return true;
        }

        if ($rowIndex !== 0) {
            return false;
        }

        foreach ($cells as $cell) {
            if (strtolower($cell->tagName) !== 'th') {
                return false;
            }
        }

        return true;
    }
}

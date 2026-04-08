<?php

namespace Src\Content\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Src\Content\Application\Actions\GenerateArticleAction;
use Src\Content\Domain\Models\Article;

class GenerateArticleCommand extends Command
{
    protected $signature = 'content:generate
        {--category= : Slug da categoria (ex: oficinas-e-centros-automotivos, pneus-alinhamento-e-balanceamento, marketing-automotivo)}
        {--track= : Filtrar por track: technical, anti_ai, b2b}
        {--index= : Índice específico do título (0-based)}
        {--all : Gerar todos os artigos pendentes da categoria}
        {--dry-run : Apenas mostrar o que seria gerado, sem salvar}
        {--provider=claude : Provedor de IA (claude ou openai)}
        {--list : Listar títulos disponíveis}';

    protected $description = 'Gera artigos SEO automaticamente usando IA a partir do article-titles-seo.json (v2.0 — estratégia híbrida)';

    private array $titlesData;

    public function __construct(
        private readonly GenerateArticleAction $generateAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->titlesData = $this->loadTitles();

        if ($this->option('list')) {
            return $this->listTitles();
        }

        $category = $this->option('category');
        if (! $category) {
            $category = $this->choice(
                'Qual categoria?',
                array_keys($this->titlesData['categories']),
                0
            );
        }

        if (! isset($this->titlesData['categories'][$category])) {
            $this->error("Categoria '$category' não encontrada.");

            return self::FAILURE;
        }

        $categoryData = $this->titlesData['categories'][$category];
        $titles = $categoryData['titles'];

        // Filtrar por track se especificado
        $trackFilter = $this->option('track');
        if ($trackFilter) {
            $titles = array_filter($titles, fn ($t) => ($t['track'] ?? '') === $trackFilter);
        }

        // Filtrar títulos já publicados
        $existingSlugs = Article::pluck('slug')->toArray();
        $pendingTitles = array_filter($titles, function ($title) use ($existingSlugs) {
            return ! in_array(Str::slug($title['title']), $existingSlugs);
        });

        if (empty($pendingTitles)) {
            $this->info("Todos os artigos de '$category' já foram gerados!");

            return self::SUCCESS;
        }

        $this->info('Encontrados '.count($pendingTitles)." artigos pendentes em '$category'");

        if ($this->option('all')) {
            return $this->generateMultiple($category, $pendingTitles);
        }

        $index = $this->option('index');
        if ($index !== null) {
            if (! isset($titles[(int) $index])) {
                $this->error("Índice $index não encontrado.");

                return self::FAILURE;
            }

            return $this->generateSingle($category, $titles[(int) $index]);
        }

        // Escolher um título
        $choices = [];
        foreach ($pendingTitles as $i => $t) {
            $choices[$i] = Str::limit($t['title'], 70);
        }

        $selectedIndex = $this->choice('Qual artigo gerar?', $choices);
        $selectedTitle = $titles[array_search($selectedIndex, $choices)];

        return $this->generateSingle($category, $selectedTitle);
    }

    private function loadTitles(): array
    {
        // Path updated to new location
        $path = base_path('src/Content/Infrastructure/Data/article-titles-seo.json');

        return json_decode(file_get_contents($path), true);
    }

    private function listTitles(): int
    {
        foreach ($this->titlesData['categories'] as $slug => $cat) {
            $this->newLine();
            $this->info("=== {$cat['name']} ({$slug}) - {$cat['target']} ===");

            $existingSlugs = Article::pluck('slug')->toArray();

            foreach ($cat['titles'] as $i => $title) {
                $articleSlug = Str::slug($title['title']);
                $status = in_array($articleSlug, $existingSlugs) ? '✓' : '○';
                $intent = match ($title['search_intent']) {
                    'informational' => 'INFO',
                    'commercial' => 'COMM',
                    'transactional' => 'TRAN',
                    default => '????'
                };
                $track = match ($title['track'] ?? '') {
                    'technical' => 'TECH',
                    'anti_ai' => 'ANTI',
                    'b2b' => 'B2B ',
                    default => '    '
                };

                $this->line("  [{$status}] {$i}: [{$intent}][{$track}] ".Str::limit($title['title'], 55));
            }
        }

        $this->newLine();
        $this->comment('✓ = já publicado | ○ = pendente');

        return self::SUCCESS;
    }

    private function generateSingle(string $categorySlug, array $titleData): int
    {
        $this->info("Gerando: {$titleData['title']}");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->comment('=== DRY RUN ===');
            $this->line("Category: $categorySlug");
            $this->line("Title: {$titleData['title']}");

            // In dry run we don't actually call the AI or save
            return self::SUCCESS;
        }

        $this->line('Chamando Action de Geração...');

        try {
            $provider = $this->option('provider');
            $article = $this->generateAction->execute($categorySlug, $titleData, $provider);

            $this->info("Artigo criado: {$article->slug}"); // Assuming there is no url attribute directly in model, slug is safer for now.

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Erro: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function generateMultiple(string $categorySlug, array $titles): int
    {
        $total = count($titles);
        $success = 0;
        $failed = 0;

        $this->info("Gerando $total artigos...");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($titles as $titleData) {
            $result = $this->generateSingle($categorySlug, $titleData);

            if ($result === self::SUCCESS) {
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();

            // Rate limiting - esperar entre chamadas
            if (! $this->option('dry-run')) {
                sleep(2);
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Concluído: $success sucesso, $failed falhas");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

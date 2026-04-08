<?php

namespace Src\Content\Console\Commands;

use Illuminate\Console\Command;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Services\ArticleLifecycleService;

class PublishScheduledArticlesCommand extends Command
{
    protected $signature = 'content:publish-scheduled';

    protected $description = 'Publish articles with status "scheduled" whose published_at has passed.';

    public function handle(ArticleLifecycleService $lifecycleService): int
    {
        $articles = Article::where('status', Article::STATUS_SCHEDULED)
            ->where('published_at', '<=', now())
            ->get();

        if ($articles->isEmpty()) {
            $this->info('No scheduled articles to publish.');

            return self::SUCCESS;
        }

        $published = 0;

        foreach ($articles as $article) {
            try {
                $lifecycleService->publish($article);
                $published++;
                $this->line("Published: [{$article->id}] {$article->title}");
            } catch (\Throwable $e) {
                $this->error("Failed to publish [{$article->id}] {$article->title}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Published {$published}/{$articles->count()} articles.");

        return self::SUCCESS;
    }
}

<?php

namespace Src\Content\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Content\Application\Controllers\ArticleController;
use Src\Content\Application\Events\CommentCreated;
use Src\Content\Application\Events\NewsletterSubscribed;
use Src\Content\Application\Listeners\NotifyAdminOfNewComment;
use Src\Content\Application\Listeners\NotifyUserOfCommentReceipt;
use Src\Content\Application\Listeners\SendNewsletterVerificationEmail;
use Src\Content\Application\Middleware\BlogRedirectMiddleware;
use Src\Content\Application\Middleware\ContentApiRateLimitResponse;
use Src\Content\Application\Middleware\VerifyContentApiToken;
use Src\Content\Console\Commands\ConsolidateCategoriesCommand;
use Src\Content\Console\Commands\GenerateArticleCommand;
use Src\Content\Console\Commands\MigrateWordPressArticlesCommand;
use Src\Content\Console\Commands\PublishScheduledArticlesCommand;
use Src\Content\Infrastructure\Cache\ContentCacheInvalidationService;

class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContentCacheInvalidationService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Presentation/Resources/views', 'content');
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConsolidateCategoriesCommand::class,
                GenerateArticleCommand::class,
                MigrateWordPressArticlesCommand::class,
                PublishScheduledArticlesCommand::class,
            ]);
        }

        $this->registerRoutes();
        $this->registerEvents();
    }

    protected function registerRoutes(): void
    {
        // Redirect 301 de URLs antigas /blog/{slug} para novas URLs (SEO - SERPs)
        Route::middleware('web')
            ->get('/blog/{slug}', function () {
                abort(404);
            })
            ->middleware(BlogRedirectMiddleware::class)
            ->name('content.blog.redirect');

        Route::middleware('web')
            ->prefix('artigos')
            ->group(__DIR__.'/../Routes/web.php');

        // Content API routes (agent-facing)
        Route::middleware([ContentApiRateLimitResponse::class, VerifyContentApiToken::class])
            ->prefix('api/content')
            ->group(__DIR__.'/../Routes/api.php');

        // Rota catch-all na raiz — registrada por último para não capturar rotas fixas de outros módulos
        Route::middleware('web')
            ->group(function () {
                Route::get('/{articleSlug}', [ArticleController::class, 'showShort'])
                    ->where('articleSlug', '[a-z0-9][a-z0-9\-]+')
                    ->name('info.article.show');
            });
    }

    protected function registerEvents(): void
    {
        Event::listen(CommentCreated::class, [NotifyAdminOfNewComment::class, 'handle']);
        Event::listen(CommentCreated::class, [NotifyUserOfCommentReceipt::class, 'handle']);
        Event::listen(NewsletterSubscribed::class, [SendNewsletterVerificationEmail::class, 'handle']);
    }
}

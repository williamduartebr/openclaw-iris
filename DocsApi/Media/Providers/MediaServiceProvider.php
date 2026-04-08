<?php

namespace Src\Media\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Media\Application\Middleware\VerifyMediaApiToken;
use Src\Media\Infrastructure\ExternalServices\Providers\GoogleGeminiImageProvider;
use Src\Media\Infrastructure\ExternalServices\Providers\OpenAiImageProvider;
use Src\Media\Infrastructure\ExternalServices\Providers\ProviderRegistry;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/media.php'), 'media');

        $this->app->singleton(ProviderRegistry::class, function () {
            $registry = new ProviderRegistry;

            $openaiConfig = config('media.providers.openai', []);
            if (! empty($openaiConfig)) {
                $registry->register(new OpenAiImageProvider($openaiConfig));
            }

            $geminiConfig = config('media.providers.google_gemini', []);
            if (! empty($geminiConfig)) {
                $registry->register(new GoogleGeminiImageProvider($geminiConfig));
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::middleware([VerifyMediaApiToken::class])
            ->prefix('api/media')
            ->group(__DIR__.'/../Routes/api.php');
    }
}

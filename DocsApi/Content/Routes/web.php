<?php

use Illuminate\Support\Facades\Route;
use Src\Content\Application\Controllers\ArticleController;
use Src\Content\Application\Controllers\NewsletterController;

// Rota catch-all na raiz — deve ser registrada por último no provider (sem prefix 'artigos')
// Ver: ContentServiceProvider::registerCatchAllRoute()

Route::get('/', [ArticleController::class, 'index'])
    ->name('content.index');

// Newsletter antes das rotas dinâmicas para evitar captura por category/article slugs.
Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])
    ->name('newsletter.subscribe');

Route::get('/newsletter/verificar', [NewsletterController::class, 'create'])
    ->name('newsletter.verify');

Route::post('/newsletter/verificar', [NewsletterController::class, 'verify'])
    ->name('newsletter.verify.post');

Route::get('/newsletter/cancelar/{token}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

Route::get('/{categorySlug}', [ArticleController::class, 'categoryIndex'])
    ->where('categorySlug', '[a-z0-9\-]+')
    ->name('content.category.index');

// Compatibilidade de nomes legados de categorias fixas.
Route::get('/dicas', [ArticleController::class, 'categoryIndex'])
    ->defaults('categorySlug', 'dicas')
    ->name('content.dicas.index');

Route::get('/{categorySlug}/{articleSlug}', [ArticleController::class, 'show'])
    ->where('articleSlug', '[a-z0-9\-]+')
    ->where('categorySlug', '[a-z0-9\-]+')
    ->name('content.category.show');

// Compatibilidade de nomes legados para artigos por categoria fixa.
Route::get('/dicas/{articleSlug}', [ArticleController::class, 'show'])
    ->where('articleSlug', '[a-z0-9\-]+')
    ->defaults('categorySlug', 'dicas')
    ->name('content.dicas.show');

Route::get('/noticias/{articleSlug}', [ArticleController::class, 'show'])
    ->where('articleSlug', '[a-z0-9\-]+')
    ->defaults('categorySlug', 'noticias')
    ->name('content.noticias.show');

Route::post('/{articleSlug}/comentar', [ArticleController::class, 'storeComment'])
    ->where('articleSlug', '[a-z0-9\-]+')
    ->defaults('categorySlug', '')
    ->name('content.article.comment.store');

Route::post('/{categorySlug}/{articleSlug}/comentar', [ArticleController::class, 'storeComment'])
    ->where('articleSlug', '[a-z0-9\-]+')
    ->where('categorySlug', '[a-z0-9\-]+')
    ->name('content.category.article.comment.store');

Route::patch('/{categorySlug}/{articleSlug}/comentarios/{commentId}', [ArticleController::class, 'updateComment'])
    ->where('articleSlug', '[a-z0-9\-]+')
    ->where('categorySlug', '[a-z0-9\-]+')
    ->whereNumber('commentId')
    ->middleware('auth')
    ->name('content.category.article.comment.update');

Route::delete('/{categorySlug}/{articleSlug}/comentarios/{commentId}', [ArticleController::class, 'destroyComment'])
    ->where('articleSlug', '[a-z0-9\-]+')
    ->where('categorySlug', '[a-z0-9\-]+')
    ->whereNumber('commentId')
    ->middleware('auth')
    ->name('content.category.article.comment.destroy');

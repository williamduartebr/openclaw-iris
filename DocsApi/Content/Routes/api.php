<?php

use Illuminate\Support\Facades\Route;
use Src\Content\Application\Controllers\ContentApiController;

/*
|--------------------------------------------------------------------------
| Content API Routes
|--------------------------------------------------------------------------
|
| Agent-facing Content API for article CRUD operations.
| All routes require Bearer token authentication via VerifyContentApiToken.
|
*/

// ── Health check (leve, sem auth) ──────────────────────────────────
Route::get('/health', [ContentApiController::class, 'health'])
    ->withoutMiddleware([\Src\Content\Application\Middleware\VerifyContentApiToken::class])
    ->middleware('throttle:120,1')
    ->name('content.api.health');

// ── Categories ─────────────────────────────────────────────────────
Route::get('/categories', [ContentApiController::class, 'categories'])
    ->middleware('throttle:120,1')
    ->name('content.api.categories');

// ── Articles: busca por slug (exata) ───────────────────────────────
Route::get('/articles/by-slug/{slug}', [ContentApiController::class, 'showBySlug'])
    ->middleware('throttle:120,1')
    ->where('slug', '[a-z0-9][a-z0-9\-]*')
    ->name('content.api.articles.bySlug');

// ── Articles: CRUD ─────────────────────────────────────────────────
Route::get('/articles', [ContentApiController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('content.api.articles.index');

Route::post('/articles', [ContentApiController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('content.api.articles.store');

Route::get('/articles/{id}', [ContentApiController::class, 'show'])
    ->middleware('throttle:120,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.show');

Route::put('/articles/{id}', [ContentApiController::class, 'update'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.update');

Route::patch('/articles/{id}', [ContentApiController::class, 'patch'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.patch');

Route::delete('/articles/{id}', [ContentApiController::class, 'destroy'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.destroy');

// ── Lifecycle actions ──────────────────────────────────────────────
Route::post('/articles/{id}/publish', [ContentApiController::class, 'publish'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.publish');

Route::post('/articles/{id}/unpublish', [ContentApiController::class, 'unpublish'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.unpublish');

Route::post('/articles/{id}/schedule', [ContentApiController::class, 'schedule'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.schedule');

Route::post('/articles/{id}/archive', [ContentApiController::class, 'archive'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.archive');

Route::post('/articles/{id}/restore', [ContentApiController::class, 'restore'])
    ->middleware('throttle:30,1')
    ->where('id', '[0-9]+')
    ->name('content.api.articles.restore');

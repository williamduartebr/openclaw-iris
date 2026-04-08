<?php

use Illuminate\Support\Facades\Route;
use Src\Media\Application\Controllers\MediaApiController;

Route::middleware('throttle:30,1')
    ->post('images/generate', [MediaApiController::class, 'generate'])
    ->name('media.api.images.generate');

Route::middleware('throttle:60,1')
    ->get('images', [MediaApiController::class, 'index'])
    ->name('media.api.images.index');

Route::middleware('throttle:60,1')
    ->get('images/{id}', [MediaApiController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('media.api.images.show');

Route::middleware('throttle:30,1')
    ->post('images/{id}/retry', [MediaApiController::class, 'retry'])
    ->where('id', '[0-9]+')
    ->name('media.api.images.retry');

Route::middleware('throttle:30,1')
    ->post('images/{id}/reprocess', [MediaApiController::class, 'reprocess'])
    ->where('id', '[0-9]+')
    ->name('media.api.images.reprocess');

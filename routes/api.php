<?php

declare(strict_types=1);

use Daikazu\Laratone\Http\Controllers\LaratoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/laratone')
    ->middleware(['api', 'laratone'])
    ->group(function (): void {
        Route::get('colorbooks', [LaratoneController::class, 'colorbooks'])
            ->name('laratone.colorbooks');

        Route::get('colorbook/{slug}', [LaratoneController::class, 'colorbook'])
            ->where('slug', '[a-z0-9-]+')
            ->name('laratone.colorbook');
    });

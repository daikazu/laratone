<?php

declare(strict_types=1);

use Daikazu\Laratone\Http\Controllers\LaratoneController;
use Illuminate\Support\Facades\Route;

$middleware = ['api'];

// Built-in throttle (default 60 requests/minute); disable with 'rate_limit' => null
$rateLimit = config('laratone.rate_limit', '60,1');
if (is_string($rateLimit) && $rateLimit !== '') {
    $middleware[] = "throttle:{$rateLimit}";
}

$middleware[] = 'laratone';

Route::prefix('api/laratone')
    ->middleware($middleware)
    ->group(function (): void {
        Route::get('colorbooks', [LaratoneController::class, 'colorbooks'])
            ->name('laratone.colorbooks');

        Route::get('colorbook/{slug}', [LaratoneController::class, 'colorbook'])
            ->where('slug', '[a-z0-9-]+')
            ->name('laratone.colorbook');

        Route::get('colorbook/{slug}/find-closest', [LaratoneController::class, 'findClosest'])
            ->where('slug', '[a-z0-9-]+')
            ->name('laratone.colorbook.find-closest');
    });

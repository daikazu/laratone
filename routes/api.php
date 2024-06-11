<?php

use Daikazu\Laratone\Http\Controllers\LaratoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/laratone')
    ->middleware('api')
    ->namespace('Daikazu\Laratone')
    ->group(function () {
        Route::get('colorbooks', [LaratoneController::class, 'colorbooks'])->name('laratone.colorbooks');
        Route::get('colorbook/{slug}', [LaratoneController::class, 'colorbook'])->name('laratone.colorbook');
    });

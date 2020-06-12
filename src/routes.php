<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api/laratone')
    ->middleware('api')
    ->namespace('Daikazu\Laratone')
    ->group(function () {
        Route::get('colorbook/{slug}', '\Daikazu\Laratone\Http\Controllers\LaratoneController@colorbook')->name('laratone.colorbook');
    });

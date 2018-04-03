<?php

namespace Daikazu\Laratone;

use Illuminate\Support\ServiceProvider;

class LaratoneServiceProvider extends ServiceProvider
{


    public function boot()
    {
        $app = $this->app;

        $this->publishes([__DIR__ . '/config/daikazu/laratone.php' => config_path('/daikazu/laratone.php')], 'laratone-config');
        $this->loadMigrationsFrom(__DIR__ . '/databases/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

    }


    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/daikazu/laratone.php', 'daikazu.laratone');


    }


}
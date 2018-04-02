<?php

namespace Daikazu\Laratone;


class LaratoneServiceProvider
{


    public function boot()
    {

        $app = $this->app;


        $this->publishes([__DIR__ . '/config/laratone.php' => config_path('daikazu/laratone.php')]);
        $this->loadMigrationsFrom(__DIR__ . '/databases/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');


    }


    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/laratone.php', 'tjm.laratone');
    }


}
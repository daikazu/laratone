<?php

namespace Daikazu\Laratone;

use Daikazu\Laratone\Commands\SeedCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../config/laratone.php';

    public function boot()
    {

        $this->registerCommands();
        $this->registerPublishables();


        $this->loadMigrationsFrom(__DIR__ . '/databases/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'laratone'
        );


        $this->app->bind('laratone', function () {
            return new Laratone();
        });
    }


    protected function registerPublishables()
    {

        $this->publishesToGroups([
            self::CONFIG_PATH => config_path('laratone.php'),
        ], ['laratone', 'laratone-config']);


    }


    protected function registerCommands()
    {
        if (!$this->app->runningInConsole()) return;

        $this->commands([
            SeedCommand::class, // laratone:seed

        ]);
    }

    protected function publishesToGroups(array $paths, $groups = null)
    {
        if (is_null($groups)) {
            $this->publishes($paths);

            return;
        }

        foreach ((array)$groups as $group) {
            $this->publishes($paths, $group);
        }
    }


}

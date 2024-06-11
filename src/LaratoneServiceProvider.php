<?php

namespace Daikazu\Laratone;

use Daikazu\Laratone\Commands\SeedCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaratoneServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laratone')
            ->hasConfigFile()
            ->hasMigrations(['create_color_books_table', 'create_colors_table'])
            ->hasCommand(SeedCommand::class)
            ->hasRoutes('api');
    }
}

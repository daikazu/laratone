<?php

declare(strict_types=1);

namespace Daikazu\Laratone;

use Daikazu\Laratone\Commands\SeedCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaratoneServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laratone')
            ->hasConfigFile()
            ->hasMigrations(['create_color_books_table', 'create_colors_table'])
            ->hasCommand(SeedCommand::class)
            ->hasRoutes('api');
    }
}

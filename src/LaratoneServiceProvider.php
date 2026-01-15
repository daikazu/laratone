<?php

declare(strict_types=1);

namespace Daikazu\Laratone;

use Daikazu\Laratone\Commands\SeedCommand;
use Daikazu\Laratone\Http\Middleware\LaratoneMiddleware;
use Illuminate\Routing\Router;
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

    public function packageBooted(): void
    {
        $router = $this->app->make(Router::class);

        // Register the laratone middleware alias with a default pass-through.
        // Users can override this in their service provider's boot method.
        $router->aliasMiddleware('laratone', LaratoneMiddleware::class);
    }
}

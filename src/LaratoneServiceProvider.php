<?php

declare(strict_types=1);

namespace Daikazu\Laratone;

use Carbon\Carbon;
use Composer\InstalledVersions;
use Daikazu\Laratone\Commands\ClearCacheCommand;
use Daikazu\Laratone\Commands\SeedCommand;
use Daikazu\Laratone\Http\Middleware\LaratoneMiddleware;
use Daikazu\Laratone\Services\ColorConverter;
use Daikazu\Laratone\Services\ColorMatcher;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

final class LaratoneServiceProvider extends ServiceProvider
{
    /**
     * Migration stubs, in the order they must run.
     *
     * @var list<string>
     */
    private const array MIGRATIONS = [
        'create_color_books_table',
        'create_colors_table',
        'add_oklch_column_to_colors_table',
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laratone.php', 'laratone');

        $this->app->singleton(Laratone::class);
        $this->app->singleton(ColorConverter::class);
        $this->app->singleton(ColorMatcher::class);
    }

    public function boot(): void
    {
        $this->commands([
            SeedCommand::class,
            ClearCacheCommand::class,
        ]);

        if (config('laratone.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        // Register the laratone middleware alias with a default pass-through.
        // Users can override this in their service provider's boot method.
        $this->app->make(Router::class)->aliasMiddleware('laratone', LaratoneMiddleware::class);

        if ($this->app->runningInConsole()) {
            $this->addAboutInformation();

            $this->publishes([
                __DIR__ . '/../config/laratone.php' => config_path('laratone.php'),
            ], 'laratone-config');

            $this->publishes($this->migrationsToPublish(), 'laratone-migrations');
        }
    }

    /**
     * Add a Laratone section to `php artisan about`.
     */
    private function addAboutInformation(): void
    {
        AboutCommand::add('Laratone', fn (): array => [
            'Version'              => InstalledVersions::getPrettyVersion('daikazu/laratone'),
            'Table Prefix'         => config('laratone.table_prefix'),
            'White Point'          => config('laratone.white_point'),
            'Match Algorithm'      => config('laratone.default_match_algorithm'),
            'Pre-calculate Colors' => config('laratone.pre_calculate_colors') ? 'ENABLED' : 'OFF',
            'Rate Limit'           => config('laratone.rate_limit') ?: 'OFF',
        ]);
    }

    /**
     * Map each migration stub to its published path. Migrations already in
     * the app keep their existing filename so re-publishing never duplicates
     * them; new ones get sequential timestamps to preserve run order.
     *
     * @return array<string, string>
     */
    private function migrationsToPublish(): array
    {
        $existing = glob(database_path('migrations/*.php')) ?: [];
        $now = Carbon::now();
        $paths = [];

        foreach (self::MIGRATIONS as $migration) {
            $timestamp = $now->addSecond()->format('Y_m_d_His');
            $published = Arr::first($existing, fn (string $file): bool => str_ends_with($file, "_{$migration}.php"));

            $paths[__DIR__ . "/../database/migrations/{$migration}.php.stub"] = $published
                ?? database_path("migrations/{$timestamp}_{$migration}.php");
        }

        return $paths;
    }
}

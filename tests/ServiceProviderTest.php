<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Daikazu\Laratone\Facades\Laratone as LaratoneFacade;
use Daikazu\Laratone\Http\Middleware\LaratoneMiddleware;
use Daikazu\Laratone\Laratone;
use Daikazu\Laratone\LaratoneServiceProvider;
use Daikazu\Laratone\Services\ColorConverter;
use Daikazu\Laratone\Services\ColorMatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * @return array<string, string> source realpath => published path
 */
function laratonePublishes(string $tag): array
{
    $paths = [];
    foreach (ServiceProvider::pathsToPublish(LaratoneServiceProvider::class, $tag) as $from => $to) {
        $paths[(string) realpath($from)] = $to;
    }

    return $paths;
}

test('package config is merged', function (): void {
    expect(config('laratone'))->toBeArray()
        ->toHaveKeys(['table_prefix', 'rate_limit']);
});

test('config file is publishable under the laratone-config tag', function (): void {
    expect(laratonePublishes('laratone-config'))->toBe([
        realpath(__DIR__ . '/../config/laratone.php') => config_path('laratone.php'),
    ]);
});

test('migrations are publishable with sequential timestamps', function (): void {
    $published = array_values(laratonePublishes('laratone-migrations'));

    $names = ['create_color_books_table', 'create_colors_table', 'add_oklch_column_to_colors_table'];

    expect($published)->toHaveCount(3);

    // Normalize separators: database_path('migrations/...') mixes / and \ on Windows
    $normalize = fn (string $path): string => str_replace('\\', '/', $path);

    foreach ($names as $i => $name) {
        expect($normalize($published[$i]))
            ->toStartWith($normalize(database_path('migrations')) . '/')
            ->toMatch('/\d{4}_\d{2}_\d{2}_\d{6}_' . $name . '\.php$/');
    }

    // Timestamps must keep the stubs in dependency order
    $sorted = $published;
    sort($sorted);
    expect($published)->toBe($sorted);
});

test('re-publishing reuses migrations already in the app', function (): void {
    $databasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laratone-' . uniqid();
    $existing = $databasePath . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '2020_01_01_000000_create_colors_table.php';
    File::ensureDirectoryExists(dirname($existing));
    File::put($existing, '<?php');

    $this->app->useDatabasePath($databasePath);

    try {
        $provider = new LaratoneServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $colorsTable = array_values(array_filter(
            laratonePublishes('laratone-migrations'),
            fn (string $to): bool => str_ends_with($to, '_create_colors_table.php'),
        ));

        expect($colorsTable)->toHaveCount(1)
            ->and(realpath($colorsTable[0]))->toBe(realpath($existing));
    } finally {
        File::deleteDirectory($databasePath);
    }
});

test('api routes are registered', function (): void {
    expect(Route::has('laratone.colorbooks'))->toBeTrue()
        ->and(Route::has('laratone.colorbook'))->toBeTrue()
        ->and(Route::has('laratone.colorbook.find-closest'))->toBeTrue();
});

test('artisan commands are registered', function (): void {
    expect(Artisan::all())->toHaveKeys(['laratone:seed', 'laratone:clear-cache']);
});

test('laratone middleware alias is registered', function (): void {
    expect(app('router')->getMiddleware())
        ->toHaveKey('laratone', LaratoneMiddleware::class);
});

test('laratone section is added to the about command', function (): void {
    Artisan::call('about', ['--only' => 'laratone', '--json' => true]);

    expect(json_decode(Artisan::output(), true))->toBe([
        'laratone' => [
            'version'              => InstalledVersions::getPrettyVersion('daikazu/laratone'),
            'table_prefix'         => 'laratone_',
            'white_point'          => 'D65',
            'match_algorithm'      => 'lab',
            'pre-calculate_colors' => 'OFF',
            'rate_limit'           => '60,1',
        ],
    ]);
});

test('services are registered as singletons', function (string $service): void {
    expect(app($service))->toBe(app($service));
})->with([Laratone::class, ColorConverter::class, ColorMatcher::class]);

test('facade resolves the shared laratone instance', function (): void {
    expect(LaratoneFacade::getFacadeRoot())->toBe(app(Laratone::class));
});

/**
 * Boot the provider against a fresh router so route registration reflects
 * the current config.
 */
function bootLaratoneRoutes(): Router
{
    $router = new Router(app('events'), app());
    app()->instance('router', $router);
    Route::clearResolvedInstance('router');

    $provider = new LaratoneServiceProvider(app());
    $provider->register();
    $provider->boot();

    $router->getRoutes()->refreshNameLookups();

    return $router;
}

test('all api routes are registered under the default prefix', function (): void {
    $routes = bootLaratoneRoutes()->getRoutes();

    expect($routes->getByName('laratone.colorbooks')->uri())->toBe('api/laratone/colorbooks')
        ->and($routes->getByName('laratone.colorbook')->uri())->toBe('api/laratone/colorbook/{slug}')
        ->and($routes->getByName('laratone.colorbook.find-closest')->uri())->toBe('api/laratone/colorbook/{slug}/find-closest')
        ->and($routes->getByName('laratone.colorbook.search')->uri())->toBe('api/laratone/colorbook/{slug}/search')
        ->and($routes->getByName('laratone.find-closest')->uri())->toBe('api/laratone/find-closest');
});

test('the route prefix is configurable', function (): void {
    config()->set('laratone.routes.prefix', 'colors/v1');

    $routes = bootLaratoneRoutes()->getRoutes();

    expect($routes->getByName('laratone.colorbooks')->uri())->toBe('colors/v1/colorbooks')
        ->and($routes->getByName('laratone.find-closest')->uri())->toBe('colors/v1/find-closest');
});

test('routes can be disabled', function (): void {
    config()->set('laratone.routes.enabled', false);

    $routes = bootLaratoneRoutes()->getRoutes();

    expect($routes->getByName('laratone.colorbooks'))->toBeNull()
        ->and($routes->count())->toBe(0);
});

test('routes stay enabled when an older published config has no routes key', function (): void {
    config()->set('laratone.routes', null);

    expect(bootLaratoneRoutes()->getRoutes()->getByName('laratone.colorbooks')?->uri())
        ->toBe('api/laratone/colorbooks');
});

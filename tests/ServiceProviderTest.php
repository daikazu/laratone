<?php

declare(strict_types=1);

use Daikazu\Laratone\Http\Middleware\LaratoneMiddleware;
use Daikazu\Laratone\LaratoneServiceProvider;
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

    foreach ($names as $i => $name) {
        expect($published[$i])
            ->toStartWith(database_path('migrations' . DIRECTORY_SEPARATOR))
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

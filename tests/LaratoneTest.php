<?php

use Daikazu\Laratone\Laratone;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {

    // Set up database configuration
    $this->app['config']->set('database.default', 'testing');
    $this->app['config']->set('database.connections.testing', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
    ]);

    // Set cart to use database storage
    $this->app['config']->set('laratone.storage', 'database');

    $this->loadMigrationsFrom(__DIR__ . '/fixtures/migrations');

    // Create the cart tables
    $this->artisan('migrate', ['--database' => 'testing']);

});

test('can get all color books', function () {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $result = $laratone->colorBooks();

    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($result->first()->name)->toBe($colorBook->name)
        ->and($result->first()->colors->first()->name)->toBe($color->name);
});

test('can get color book by slug', function () {
    $colorBook = ColorBook::factory()->create(['slug' => 'test-book']);
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $result = $laratone->colorBookBySlug('test-book');

    expect($result)->toBeInstanceOf(ColorBook::class)
        ->and($result->slug)->toBe('test-book')
        ->and($result->name)->toBe($colorBook->name);
});

test('returns null for non-existent color book slug', function () {
    $laratone = new Laratone;
    $result = $laratone->colorBookBySlug('non-existent');

    expect($result)->toBeNull();
});

test('can create a new color book', function () {
    $laratone = new Laratone;
    $result = $laratone->createColorBook('Test Book');

    expect($result)->toBeInstanceOf(ColorBook::class)
        ->and($result->name)->toBe('Test Book')
        ->and($result->slug)->toBe('test-book');
});

test('can create color book with custom slug', function () {
    $laratone = new Laratone;
    $result = $laratone->createColorBook('Test Book', 'custom-slug');

    expect($result)->toBeInstanceOf(ColorBook::class)
        ->and($result->name)->toBe('Test Book')
        ->and($result->slug)->toBe('custom-slug');
});

test('can add color to book', function () {
    $colorBook = ColorBook::factory()->create();
    $laratone = new Laratone;

    $colorData = [
        'name' => 'Test Color',
        'hex'  => '#FF0000',
        'rgb'  => '255,0,0',
    ];

    $result = $laratone->addColorToBook($colorBook, $colorData);

    expect($result)->toBeInstanceOf(Color::class)
        ->and($result->name)->toBe('Test Color')
        ->and($result->hex)->toBe('#FF0000')
        ->and($result->color_book_id)->toBe($colorBook->id);
});

test('can add multiple colors to book', function () {
    $colorBook = ColorBook::factory()->create();
    $laratone = new Laratone;

    $colorsData = [
        ['name' => 'Color 1', 'hex' => '#FF0000', 'rgb' => '255,0,0'],
        ['name' => 'Color 2', 'hex' => '#00FF00', 'rgb' => '0,255,0'],
    ];

    $result = $laratone->addColorsToBook($colorBook, $colorsData);

    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($result->count())->toBe(2)
        ->and($result->first()->name)->toBe('Color 1')
        ->and($result->last()->name)->toBe('Color 2');
});

test('can update color', function () {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);
    $laratone = new Laratone;

    $result = $laratone->updateColor($color, ['name' => 'Updated Color']);

    expect($result)->toBeTrue()
        ->and($color->fresh()->name)->toBe('Updated Color');
});

test('can delete color', function () {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);
    $laratone = new Laratone;

    $result = $laratone->deleteColor($color);

    expect($result)->toBeTrue()
        ->and(Color::find($color->id))->toBeNull();
});

test('can get colors from book', function () {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);
    $laratone = new Laratone;

    $result = $laratone->getColorsFromBook($colorBook);

    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($result->first()->name)->toBe($color->name);
});

test('clears cache when modifying data', function () {
    $colorBook = ColorBook::factory()->create();
    $laratone = new Laratone;

    // Prime the cache
    $laratone->colorBooks();

    // Modify data
    $laratone->createColorBook('New Book');

    // Check if cache was cleared
    expect(Cache::has('laratone.color_books'))->toBeFalse();
});

<?php

declare(strict_types=1);

use Daikazu\Laratone\Laratone;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

test('can get all color books', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $result = $laratone->colorBooks();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->first()->name)->toBe($colorBook->name)
        ->and($result->first()->colors->first()->name)->toBe($color->name);
});

test('can get color book by slug', function (): void {
    $colorBook = ColorBook::factory()->create(['slug' => 'test-book']);
    Color::factory()->create(['color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $result = $laratone->colorBookBySlug('test-book');

    expect($result)->toBeInstanceOf(ColorBook::class)
        ->and($result->slug)->toBe('test-book')
        ->and($result->name)->toBe($colorBook->name);
});

test('returns null for non-existent color book slug', function (): void {
    $laratone = new Laratone;
    $result = $laratone->colorBookBySlug('non-existent');

    expect($result)->toBeNull();
});

test('can create a new color book', function (): void {
    $laratone = new Laratone;
    $result = $laratone->createColorBook('Test Book');

    expect($result)->toBeInstanceOf(ColorBook::class)
        ->and($result->name)->toBe('Test Book')
        ->and($result->slug)->toBe('test-book');
});

test('can create color book with custom slug', function (): void {
    $laratone = new Laratone;
    $result = $laratone->createColorBook('Test Book', 'custom-slug');

    expect($result)->toBeInstanceOf(ColorBook::class)
        ->and($result->name)->toBe('Test Book')
        ->and($result->slug)->toBe('custom-slug');
});

test('can add color to book', function (): void {
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

test('can add multiple colors to book', function (): void {
    $colorBook = ColorBook::factory()->create();
    $laratone = new Laratone;

    $colorsData = [
        ['name' => 'Color 1', 'hex' => '#FF0000', 'rgb' => '255,0,0'],
        ['name' => 'Color 2', 'hex' => '#00FF00', 'rgb' => '0,255,0'],
    ];

    $result = $laratone->addColorsToBook($colorBook, $colorsData);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and($result->first()->name)->toBe('Color 1')
        ->and($result->last()->name)->toBe('Color 2');
});

test('can update color', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);
    $laratone = new Laratone;

    $result = $laratone->updateColor($color, ['name' => 'Updated Color']);

    expect($result)->toBeTrue()
        ->and($color->fresh()->name)->toBe('Updated Color');
});

test('can delete color', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);
    $laratone = new Laratone;

    $result = $laratone->deleteColor($color);

    expect($result)->toBeTrue()
        ->and(Color::find($color->id))->toBeNull();
});

test('can get colors from book', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);
    $laratone = new Laratone;

    $result = $laratone->getColorsFromBook($colorBook);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->first()->name)->toBe($color->name);
});

test('clears cache when modifying data', function (): void {
    ColorBook::factory()->create();
    $laratone = new Laratone;

    // Prime the cache
    $laratone->colorBooks();

    // Modify data
    $laratone->createColorBook('New Book');

    // Check if cache was cleared
    expect(Cache::has('laratone.color_books'))->toBeFalse();
});

// Find Closest Colors Tests

test('can find closest colors from color book', function (): void {
    $colorBook = ColorBook::factory()->create(['slug' => 'find-closest-test-' . uniqid()]);
    // Use create() directly on the model to avoid factory generating random color space values
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $laratone->clearCache();
    $result = $laratone->findClosestColors($colorBook, 'FF5500', 1, 'lab');

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Red')
        ->and($result->first()->distance)->toBeFloat();
});

test('can find multiple closest colors', function (): void {
    $colorBook = ColorBook::factory()->create(['slug' => 'multiple-closest-test-' . uniqid()]);
    // Use create() directly on the model to avoid factory generating random color space values
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Orange', 'hex' => 'FF5500', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $laratone->clearCache();
    $result = $laratone->findClosestColors($colorBook, 'FF3300', 2, 'lab');

    expect($result)->toHaveCount(2);
});

test('can find closest colors using oklch algorithm', function (): void {
    $colorBook = ColorBook::factory()->create(['slug' => 'oklch-test-' . uniqid()]);
    // Use create() directly on the model to avoid factory generating random color space values
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $laratone->clearCache();
    $result = $laratone->findClosestColors($colorBook, 'FF5500', 1, 'oklch');

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Red');
});

test('find closest colors defaults to limit 1 and lab algorithm', function (): void {
    $colorBook = ColorBook::factory()->create(['slug' => 'defaults-test-' . uniqid()]);
    // Use create() directly on the model to avoid factory generating random color space values
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $laratone = new Laratone;
    $laratone->clearCache();
    $result = $laratone->findClosestColors($colorBook, 'FF5500');

    expect($result)->toHaveCount(1);
});

<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

// ColorBook Model Tests

test('color book has correct table name with prefix', function (): void {
    $colorBook = new ColorBook;

    expect($colorBook->getTable())->toBe('laratone_color_books');
});

test('color book has colors relationship', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);

    expect($colorBook->colors)->toHaveCount(1)
        ->and($colorBook->colors->first()->name)->toBe($color->name);
});

test('color book scope filters by slug', function (): void {
    ColorBook::factory()->create(['slug' => 'first-book']);
    ColorBook::factory()->create(['slug' => 'second-book']);

    $result = ColorBook::slug('first-book')->first();

    expect($result)->not->toBeNull()
        ->and($result->slug)->toBe('first-book');
});

test('color book scope returns null for non-existent slug', function (): void {
    ColorBook::factory()->create(['slug' => 'existing-book']);

    $result = ColorBook::slug('non-existent')->first();

    expect($result)->toBeNull();
});

test('color book hides id and timestamps in json', function (): void {
    $colorBook = ColorBook::factory()->create();
    $json = $colorBook->toArray();

    expect($json)->not->toHaveKey('id')
        ->and($json)->not->toHaveKey('created_at')
        ->and($json)->not->toHaveKey('updated_at')
        ->and($json)->toHaveKey('name')
        ->and($json)->toHaveKey('slug');
});

// Color Model Tests

test('color has correct table name with prefix', function (): void {
    $color = new Color;

    expect($color->getTable())->toBe('laratone_colors');
});

test('color belongs to color book', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);

    expect($color->colorBook)->toBeInstanceOf(ColorBook::class)
        ->and($color->colorBook->id)->toBe($colorBook->id);
});

test('color hides id and foreign key in json', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);
    $json = $color->toArray();

    expect($json)->not->toHaveKey('id')
        ->and($json)->not->toHaveKey('color_book_id')
        ->and($json)->not->toHaveKey('created_at')
        ->and($json)->not->toHaveKey('updated_at')
        ->and($json)->toHaveKey('name');
});

test('color can store all color format values', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Complete Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF5500',
        'lab'           => '60.32,45.21,58.90',
        'rgb'           => '255,85,0',
        'cmyk'          => '0,67,100,0',
    ]);

    expect($color->hex)->toBe('FF5500')
        ->and($color->lab)->toBe(['l' => 60.32, 'a' => 45.21, 'b' => 58.90])
        ->and($color->rgb)->toBe(['r' => 255, 'g' => 85, 'b' => 0])
        ->and($color->cmyk)->toBe(['c' => 0, 'm' => 67, 'y' => 100, 'k' => 0]);
});

// Auto-calculation Tests

test('color auto-calculates rgb from hex when rgb is null', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
    ]);

    expect($color->rgb)->toBe(['r' => 255, 'g' => 0, 'b' => 0]);
});

test('color auto-calculates cmyk from hex when cmyk is null', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
    ]);

    expect($color->cmyk)->toBe(['c' => 0, 'm' => 100, 'y' => 100, 'k' => 0]);
});

test('color auto-calculates lab from hex when lab is null', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
    ]);

    // LAB values for red should be approximately L=53, a=80, b=67
    expect($color->lab['l'])->toBeGreaterThan(50)
        ->and($color->lab['a'])->toBeGreaterThan(70);
});

test('color uses stored value over calculated when provided', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Custom Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        // Use custom LAB values (like official Pantone values)
        'lab' => '50.0,75.0,60.0',
    ]);

    // Should use stored value, not calculated
    expect($color->lab)->toBe(['l' => 50.0, 'a' => 75.0, 'b' => 60.0]);
});

test('color with only hex can return all color formats', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Orange',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF5500',
    ]);

    // All formats should be available
    expect($color->hex)->toBe('FF5500')
        ->and($color->rgb)->toBe(['r' => 255, 'g' => 85, 'b' => 0])
        ->and($color->cmyk['c'])->toBe(0)
        ->and($color->cmyk['k'])->toBe(0)
        ->and($color->lab)->toBeArray()
        ->and($color->lab)->toHaveKeys(['l', 'a', 'b']);
});

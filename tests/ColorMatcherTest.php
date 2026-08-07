<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Daikazu\Laratone\Services\ColorConverter;
use Daikazu\Laratone\Services\ColorMatcher;
use Illuminate\Database\Eloquent\Collection;

// Basic functionality tests

test('finds single closest color in LAB space', function (): void {
    $colorBook = ColorBook::factory()->create();

    // Create colors with known LAB values
    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    $green = Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);
    $blue = Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red, $green, $blue]);
    $matcher = new ColorMatcher(new ColorConverter);

    // Find closest to orange (should be red)
    $result = $matcher->findClosest('FF5500', $colors, 1, 'lab');

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Red')
        ->and($result->first()->distance)->toBeFloat();
});

test('finds multiple closest colors', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    $orange = Color::create(['name' => 'Orange', 'hex' => 'FF5500', 'color_book_id' => $colorBook->id]);
    $yellow = Color::create(['name' => 'Yellow', 'hex' => 'FFFF00', 'color_book_id' => $colorBook->id]);
    $green = Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red, $orange, $yellow, $green]);
    $matcher = new ColorMatcher(new ColorConverter);

    // Find 3 closest to red-orange
    $result = $matcher->findClosest('FF3300', $colors, 3, 'lab');

    expect($result)->toHaveCount(3)
        ->and($result->pluck('name')->toArray())->toContain('Red')
        ->and($result->pluck('name')->toArray())->toContain('Orange');
});

test('returns colors sorted by distance ascending', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    $green = Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);
    $blue = Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red, $green, $blue]);
    $matcher = new ColorMatcher(new ColorConverter);

    $result = $matcher->findClosest('FF0000', $colors, 3, 'lab');

    // First should be exact match (Red) with distance close to 0
    expect($result->first()->name)->toBe('Red')
        ->and($result->first()->distance)->toBeLessThan(1);

    // Distances should be in ascending order
    $distances = $result->pluck('distance')->toArray();
    expect($distances[0])->toBeLessThanOrEqual($distances[1])
        ->and($distances[1])->toBeLessThanOrEqual($distances[2]);
});

// Algorithm tests

test('supports OKLCH algorithm', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    $blue = Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red, $blue]);
    $matcher = new ColorMatcher(new ColorConverter);

    // Find closest to orange using OKLCH
    $result = $matcher->findClosest('FF5500', $colors, 1, 'oklch');

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Red')
        ->and($result->first()->distance)->toBeFloat();
});

test('LAB and OKLCH may produce different rankings', function (): void {
    $colorBook = ColorBook::factory()->create();

    // Create colors that might rank differently in different color spaces
    $color1 = Color::create(['name' => 'Color 1', 'hex' => 'FF7700', 'color_book_id' => $colorBook->id]);
    $color2 = Color::create(['name' => 'Color 2', 'hex' => 'FF0077', 'color_book_id' => $colorBook->id]);
    $color3 = Color::create(['name' => 'Color 3', 'hex' => 'CC4444', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$color1, $color2, $color3]);
    $matcher = new ColorMatcher(new ColorConverter);

    $labResult = $matcher->findClosest('FF5500', $colors, 3, 'lab');
    $oklchResult = $matcher->findClosest('FF5500', $colors, 3, 'oklch');

    // Both should return 3 results
    expect($labResult)->toHaveCount(3)
        ->and($oklchResult)->toHaveCount(3);

    // Results are valid (we're not asserting they're different, just that both work)
    expect($labResult->first()->distance)->toBeFloat()
        ->and($oklchResult->first()->distance)->toBeFloat();
});

test('throws exception for invalid algorithm', function (): void {
    $colors = new Collection;
    $matcher = new ColorMatcher(new ColorConverter);

    $matcher->findClosest('FF0000', $colors, 1, 'invalid');
})->throws(InvalidArgumentException::class, "Invalid algorithm 'invalid'");

test('available algorithms returns valid options', function (): void {
    $algorithms = ColorMatcher::availableAlgorithms();

    expect($algorithms)->toContain('lab')
        ->and($algorithms)->toContain('oklch');
});

// Edge cases

test('returns empty collection when no colors provided', function (): void {
    $colors = new Collection;
    $matcher = new ColorMatcher(new ColorConverter);

    $result = $matcher->findClosest('FF0000', $colors, 5, 'lab');

    expect($result)->toBeEmpty();
});

test('returns all colors when limit exceeds collection size', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    $green = Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red, $green]);
    $matcher = new ColorMatcher(new ColorConverter);

    // Ask for 10 but only 2 exist
    $result = $matcher->findClosest('FF0000', $colors, 10, 'lab');

    expect($result)->toHaveCount(2);
});

test('handles exact color match with zero distance', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red]);
    $matcher = new ColorMatcher(new ColorConverter);

    // Search for exact same color
    $result = $matcher->findClosest('FF0000', $colors, 1, 'lab');

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Red')
        ->and($result->first()->distance)->toBeLessThan(0.01);
});

test('handles hex input with hash prefix', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red]);
    $matcher = new ColorMatcher(new ColorConverter);

    // Input with # prefix should work
    $result = $matcher->findClosest('#FF0000', $colors, 1, 'lab');

    expect($result)->toHaveCount(1);
});

test('handles lowercase hex input', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red]);
    $matcher = new ColorMatcher(new ColorConverter);

    // Lowercase input should work
    $result = $matcher->findClosest('ff0000', $colors, 1, 'lab');

    expect($result)->toHaveCount(1);
});

// Distance value tests

test('attaches distance attribute to returned colors', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$red]);
    $matcher = new ColorMatcher(new ColorConverter);

    $result = $matcher->findClosest('FF5500', $colors, 1, 'lab');

    expect($result->first()->distance)->toBeFloat()
        ->and($result->first()->distance)->toBeGreaterThan(0);
});

test('distance values are rounded to 4 decimal places', function (): void {
    $colorBook = ColorBook::factory()->create();

    $color = Color::create(['name' => 'Test', 'hex' => 'AABBCC', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$color]);
    $matcher = new ColorMatcher(new ColorConverter);

    $result = $matcher->findClosest('AABBDD', $colors, 1, 'lab');
    $distanceString = (string) $result->first()->distance;

    // Check that there are at most 4 decimal places
    $parts = explode('.', $distanceString);
    if (isset($parts[1])) {
        expect(strlen($parts[1]))->toBeLessThanOrEqual(4);
    }
});

test('oklch weights hue properly so a desaturated red beats a cyan for a red target', function (): void {
    $colorBook = ColorBook::factory()->create();

    $desaturatedRed = Color::create(['name' => 'Desaturated Red', 'hex' => 'CC6655', 'color_book_id' => $colorBook->id]);
    $cyan = Color::create(['name' => 'Cyan', 'hex' => '00A5AD', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$cyan, $desaturatedRed]);
    $matcher = new ColorMatcher(new ColorConverter);

    $result = $matcher->findClosest('FF0000', $colors, 1, 'oklch');

    expect($result->first()->name)->toBe('Desaturated Red');
});

test('skips colors whose color values cannot be resolved', function (): void {
    $colorBook = ColorBook::factory()->create();

    $noHex = Color::create(['name' => 'No Hex', 'hex' => '', 'color_book_id' => $colorBook->id]);
    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $colors = new Collection([$noHex, $red]);
    $matcher = new ColorMatcher(new ColorConverter);

    $result = $matcher->findClosest('FF0000', $colors, 5);

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Red');
});

test('does not leave the distance attribute dirty on searched models', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $matcher = new ColorMatcher(new ColorConverter);
    $match = $matcher->findClosest('FF5500', new Collection([$red]), 1)->first();

    expect($match->getAttribute('distance'))->toBeFloat()
        ->and($red->isDirty())->toBeFalse();

    // A matched model can be saved without the transient distance attribute
    // leaking into the UPDATE statement
    $match->name = 'Renamed';
    $match->save();

    expect(Color::where('name', 'Renamed')->exists())->toBeTrue();
});

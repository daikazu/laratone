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

// CIEDE2000 (Delta E 2000) tests

/*
 * Reference data from Sharma, Wu & Dalal (2005), "The CIEDE2000 Color-Difference
 * Formula: Implementation Notes, Supplementary Test Data, and Mathematical
 * Observations", Table 1. Each row: [L1, a1, b1], [L2, a2, b2], expected Delta E 2000.
 */
dataset('sharma_ciede2000', [
    [[50.0000, 2.6772, -79.7751], [50.0000, 0.0000, -82.7485], 2.0425],
    [[50.0000, 3.1571, -77.2803], [50.0000, 0.0000, -82.7485], 2.8615],
    [[50.0000, 2.8361, -74.0200], [50.0000, 0.0000, -82.7485], 3.4412],
    [[50.0000, -1.3802, -84.2814], [50.0000, 0.0000, -82.7485], 1.0000],
    [[50.0000, -1.1848, -84.8006], [50.0000, 0.0000, -82.7485], 1.0000],
    [[50.0000, -0.9009, -85.5211], [50.0000, 0.0000, -82.7485], 1.0000],
    [[50.0000, 0.0000, 0.0000], [50.0000, -1.0000, 2.0000], 2.3669],
    [[50.0000, -1.0000, 2.0000], [50.0000, 0.0000, 0.0000], 2.3669],
    [[50.0000, 2.4900, -0.0010], [50.0000, -2.4900, 0.0009], 7.1792],
    [[50.0000, 2.4900, -0.0010], [50.0000, -2.4900, 0.0010], 7.1792],
    [[50.0000, 2.4900, -0.0010], [50.0000, -2.4900, 0.0011], 7.2195],
    [[50.0000, 2.4900, -0.0010], [50.0000, -2.4900, 0.0012], 7.2195],
    [[50.0000, -0.0010, 2.4900], [50.0000, 0.0009, -2.4900], 4.8045],
    [[50.0000, -0.0010, 2.4900], [50.0000, 0.0010, -2.4900], 4.8045],
    [[50.0000, -0.0010, 2.4900], [50.0000, 0.0011, -2.4900], 4.7461],
    [[50.0000, 2.5000, 0.0000], [50.0000, 0.0000, -2.5000], 4.3065],
    [[50.0000, 2.5000, 0.0000], [73.0000, 25.0000, -18.0000], 27.1492],
    [[50.0000, 2.5000, 0.0000], [61.0000, -5.0000, 29.0000], 22.8977],
    [[50.0000, 2.5000, 0.0000], [56.0000, -27.0000, -3.0000], 31.9030],
    [[50.0000, 2.5000, 0.0000], [58.0000, 24.0000, 15.0000], 19.4535],
    [[50.0000, 2.5000, 0.0000], [50.0000, 3.1736, 0.5854], 1.0000],
    [[50.0000, 2.5000, 0.0000], [50.0000, 3.2972, 0.0000], 1.0000],
    [[50.0000, 2.5000, 0.0000], [50.0000, 1.8634, 0.5757], 1.0000],
    [[50.0000, 2.5000, 0.0000], [50.0000, 3.2592, 0.3350], 1.0000],
    [[60.2574, -34.0099, 36.2677], [60.4626, -34.1751, 39.4387], 1.2644],
    [[63.0109, -31.0961, -5.8663], [62.8187, -29.7946, -4.0864], 1.2630],
    [[61.2901, 3.7196, -5.3901], [61.4292, 2.2480, -4.9620], 1.8731],
    [[35.0831, -44.1164, 3.7933], [35.0232, -40.0716, 1.5901], 1.8645],
    [[22.7233, 20.0904, -46.6940], [23.0331, 14.9730, -42.5619], 2.0373],
    [[36.4612, 47.8580, 18.3852], [36.2715, 50.5065, 21.2231], 1.4146],
    [[90.8027, -2.0831, 1.4410], [91.1528, -1.6435, 0.0447], 1.4441],
    [[90.9257, -0.5406, -0.9208], [88.6381, -0.8985, -0.7239], 1.5381],
    [[6.7747, -0.2908, -2.4247], [5.8714, -0.0985, -2.2286], 0.6377],
    [[2.0776, 0.0795, -1.1350], [0.9033, -0.0636, -0.5514], 0.9082],
]);

test('calculates Delta E 2000 matching the Sharma reference data', function (array $lab1, array $lab2, float $expected): void {
    $matcher = new ColorMatcher(new ColorConverter);

    $toLab = fn (array $v): array => ['l' => $v[0], 'a' => $v[1], 'b' => $v[2]];

    expect(round($matcher->deltaE2000($toLab($lab1), $toLab($lab2)), 4))->toBe($expected)
        // Delta E 2000 is symmetric
        ->and(round($matcher->deltaE2000($toLab($lab2), $toLab($lab1)), 4))->toBe($expected);
})->with('sharma_ciede2000');

test('Delta E 2000 of identical colors is zero', function (): void {
    $matcher = new ColorMatcher(new ColorConverter);
    $lab = ['l' => 53.23, 'a' => 80.11, 'b' => 67.22];

    expect($matcher->deltaE2000($lab, $lab))->toBe(0.0);
});

test('finds closest color with the ciede2000 algorithm', function (): void {
    $colorBook = ColorBook::factory()->create();

    $red = Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    $green = Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);
    $blue = Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $matcher = new ColorMatcher(new ColorConverter);
    $result = $matcher->findClosest('FF5500', new Collection([$red, $green, $blue]), 3, 'ciede2000');

    expect($result)->toHaveCount(3)
        ->and($result->first()->name)->toBe('Red')
        ->and($result->first()->distance)->toBeFloat()
        ->and($result->pluck('distance')->all())->toBe($result->pluck('distance')->sort()->values()->all());
});

test('ciede2000 distance for an exact match is zero', function (): void {
    $colorBook = ColorBook::factory()->create();
    $orange = Color::create(['name' => 'Orange', 'hex' => 'FF5500', 'color_book_id' => $colorBook->id]);

    $matcher = new ColorMatcher(new ColorConverter);
    $result = $matcher->findClosest('FF5500', new Collection([$orange]), 1, 'ciede2000');

    expect($result->first()->distance)->toBe(0.0);
});

test('ciede2000 is listed as an available algorithm', function (): void {
    expect(ColorMatcher::availableAlgorithms())->toContain('ciede2000');
});

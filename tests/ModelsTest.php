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

test('color auto-calculates oklch from hex when oklch is null', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
    ]);

    // OKLCH values for red should be approximately L=0.63, C=0.26, H=29
    expect($color->oklch['l'])->toBeGreaterThan(0.6)
        ->and($color->oklch['l'])->toBeLessThan(0.7)
        ->and($color->oklch['c'])->toBeGreaterThan(0.2)
        ->and($color->oklch['h'])->toBeGreaterThan(20)
        ->and($color->oklch['h'])->toBeLessThan(35);
});

test('color uses stored value over calculated when provided', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Custom Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        // Use custom LAB values (like official Solid Coated values)
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

test('calculateAllFromHex returns all color formats from hex', function (): void {
    $values = Color::calculateAllFromHex('FF5500');

    expect($values)->toHaveKeys(['rgb', 'cmyk', 'lab', 'oklch'])
        ->and($values['rgb'])->toBe(['r' => 255, 'g' => 85, 'b' => 0])
        ->and($values['cmyk']['c'])->toBe(0)
        ->and($values['cmyk']['k'])->toBe(0)
        ->and($values['lab'])->toHaveKeys(['l', 'a', 'b'])
        ->and($values['oklch'])->toHaveKeys(['l', 'c', 'h']);
});

test('calculateAllFromHex accepts custom white point', function (): void {
    $valuesD65 = Color::calculateAllFromHex('FF5500', 'D65');
    $valuesD50 = Color::calculateAllFromHex('FF5500', 'D50');

    // LAB values should differ between white points
    expect($valuesD65['lab'])->not->toBe($valuesD50['lab']);
});

// Auto-persist Tests

test('does not persist calculated values when pre_calculate_colors is disabled', function (): void {
    config(['laratone.pre_calculate_colors' => false]);

    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
    ]);

    // Refresh from database to check actual stored values
    $color->refresh();

    // Raw database values should be null
    expect($color->getAttributes()['rgb'])->toBeNull()
        ->and($color->getAttributes()['cmyk'])->toBeNull()
        ->and($color->getAttributes()['lab'])->toBeNull()
        ->and($color->getAttributes()['oklch'])->toBeNull();
});

test('persists calculated values when pre_calculate_colors is enabled', function (): void {
    config(['laratone.pre_calculate_colors' => true]);

    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
    ]);

    // Refresh from database to check actual stored values
    $color->refresh();

    // Raw database values should be populated
    expect($color->getAttributes()['rgb'])->toBe('255,0,0')
        ->and($color->getAttributes()['cmyk'])->toBe('0,100,100,0')
        ->and($color->getAttributes()['lab'])->not->toBeNull()
        ->and($color->getAttributes()['oklch'])->not->toBeNull();
});

test('does not overwrite explicitly provided values when pre_calculate_colors is enabled', function (): void {
    config(['laratone.pre_calculate_colors' => true]);

    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Custom Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        'lab'           => '50.0,75.0,60.0', // Custom LAB value (e.g., official Solid Coated)
        'oklch'         => '0.5,0.2,30.0',  // Custom OKLCH value
    ]);

    $color->refresh();

    // Custom values should be preserved, not overwritten
    expect($color->getAttributes()['lab'])->toBe('50.0,75.0,60.0')
        ->and($color->getAttributes()['oklch'])->toBe('0.5,0.2,30.0')
        // But RGB and CMYK should be calculated
        ->and($color->getAttributes()['rgb'])->toBe('255,0,0')
        ->and($color->getAttributes()['cmyk'])->toBe('0,100,100,0');
});

test('recalculates values when hex changes and pre_calculate_colors is enabled', function (): void {
    config(['laratone.pre_calculate_colors' => true]);

    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Red',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
    ]);

    // Change hex to green
    $color->hex = '00FF00';
    $color->rgb = null; // Clear to allow recalculation
    $color->cmyk = null;
    $color->lab = null;
    $color->oklch = null;
    $color->save();

    $color->refresh();

    // Should have green values now
    expect($color->getAttributes()['rgb'])->toBe('0,255,0')
        ->and($color->getAttributes()['oklch'])->not->toBeNull();
});

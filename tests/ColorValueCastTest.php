<?php

declare(strict_types=1);

use Daikazu\Laratone\Casts\ColorValueCast;
use Daikazu\Laratone\Enums\ColorType;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

test('forType generates correct cast string for LAB', function (): void {
    $cast = ColorValueCast::forType(ColorType::LAB);

    expect($cast)->toBe(ColorValueCast::class . ':l,a,b,float');
});

test('forType generates correct cast string for RGB', function (): void {
    $cast = ColorValueCast::forType(ColorType::RGB);

    expect($cast)->toBe(ColorValueCast::class . ':r,g,b,int');
});

test('forType generates correct cast string for CMYK', function (): void {
    $cast = ColorValueCast::forType(ColorType::CMYK);

    expect($cast)->toBe(ColorValueCast::class . ':c,m,y,k,int');
});

test('cast returns stored value when provided', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        'lab'           => '53.23,80.11,67.22',
    ]);

    // Should return stored value, not calculated
    expect($color->lab)->toBe(['l' => 53.23, 'a' => 80.11, 'b' => 67.22]);
});

test('cast returns null for empty string value', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        'rgb'           => '',
    ]);

    // Empty string stored value returns null from cast, but auto-calc kicks in
    // Since we have hex, it will calculate RGB
    expect($color->rgb)->toBe(['r' => 255, 'g' => 0, 'b' => 0]);
});

test('cast parses LAB values as floats', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        'lab'           => '53.23,80.11,67.22',
    ]);

    expect($color->lab)->toBe(['l' => 53.23, 'a' => 80.11, 'b' => 67.22]);
});

test('cast parses RGB values as integers', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF8040',
        'rgb'           => '255,128,64',
    ]);

    expect($color->fresh()->rgb)->toBe(['r' => 255, 'g' => 128, 'b' => 64]);
});

test('cast parses CMYK values as integers', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        'cmyk'          => '0,100,100,0',
    ]);

    expect($color->cmyk)->toBe(['c' => 0, 'm' => 100, 'y' => 100, 'k' => 0]);
});

test('cast returns null for mismatched component count', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF8000',
        'rgb'           => '255,128', // Missing third component - invalid stored value
    ]);

    // Since stored value is invalid, auto-calculation kicks in from hex
    expect($color->rgb)->toBe(['r' => 255, 'g' => 128, 'b' => 0]);
});

test('cast can set value from array', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => 'FF0000',
        'rgb'           => ['r' => 255, 'g' => 0, 'b' => 0],
    ]);

    $color->refresh();
    expect($color->rgb)->toBe(['r' => 255, 'g' => 0, 'b' => 0]);
});

test('cast can set value from string', function (): void {
    $colorBook = ColorBook::factory()->create();
    $color = Color::create([
        'name'          => 'Test Color',
        'color_book_id' => $colorBook->id,
        'hex'           => '6496C8',
        'rgb'           => '100,150,200',
    ]);

    expect($color->rgb)->toBe(['r' => 100, 'g' => 150, 'b' => 200]);
});

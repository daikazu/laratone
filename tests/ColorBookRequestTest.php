<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

test('validates limit must be an integer', function (): void {
    ColorBook::create(['name' => 'Test', 'slug' => 'test']);

    $response = $this->getJson('/api/laratone/colorbook/test?limit=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['limit']);
});

test('validates limit must be at least 1', function (): void {
    ColorBook::create(['name' => 'Test', 'slug' => 'test']);

    $response = $this->getJson('/api/laratone/colorbook/test?limit=0');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['limit']);
});

test('validates sort must be asc or desc', function (): void {
    ColorBook::create(['name' => 'Test', 'slug' => 'test']);

    $response = $this->getJson('/api/laratone/colorbook/test?sort=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sort']);
});

test('validates random must be boolean', function (): void {
    ColorBook::create(['name' => 'Test', 'slug' => 'test']);

    $response = $this->getJson('/api/laratone/colorbook/test?random=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['random']);
});

test('accepts valid limit parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test', 'slug' => 'test']);
    Color::create(['name' => 'Color 1', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 2', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test?limit=1');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'colors');
});

test('accepts valid sort parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test', 'slug' => 'test']);
    Color::create(['name' => 'B Color', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'A Color', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test?sort=desc');

    $response->assertStatus(200)
        ->assertJsonPath('colors.0.name', 'B Color');
});

test('accepts valid random parameter as boolean', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test', 'slug' => 'test']);
    Color::create(['name' => 'Color 1', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test?random=true');

    $response->assertStatus(200);
});

test('accepts valid random parameter as integer', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test', 'slug' => 'test']);
    Color::create(['name' => 'Color 1', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test?random=1');

    $response->assertStatus(200);
});

test('accepts multiple valid parameters', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test', 'slug' => 'test']);
    Color::create(['name' => 'Color 1', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 2', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 3', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test?limit=2&sort=asc');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'colors');
});

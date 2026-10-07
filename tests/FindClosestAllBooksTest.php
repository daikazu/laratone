<?php

declare(strict_types=1);

use Daikazu\Laratone\Facades\Laratone;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

beforeEach(function (): void {
    $inks = ColorBook::create(['name' => 'Inks', 'slug' => 'inks']);
    Color::create(['name' => 'Ink Red', 'hex' => 'FF0000', 'color_book_id' => $inks->id]);
    Color::create(['name' => 'Ink Blue', 'hex' => '0000FF', 'color_book_id' => $inks->id]);

    $threads = ColorBook::create(['name' => 'Threads', 'slug' => 'threads']);
    Color::create(['name' => 'Thread Orange', 'hex' => 'FF5A00', 'color_book_id' => $threads->id]);
    Color::create(['name' => 'Thread Green', 'hex' => '00FF00', 'color_book_id' => $threads->id]);
});

// Programmatic API

test('finds the closest color across all books', function (): void {
    $closest = Laratone::findClosestColorsInAllBooks('FF5500');

    expect($closest)->toHaveCount(1)
        ->and($closest->first()->name)->toBe('Thread Orange')
        ->and($closest->first()->colorBook->slug)->toBe('threads')
        ->and($closest->first()->distance)->toBeFloat();
});

test('returns closest colors from multiple books in distance order', function (): void {
    $closest = Laratone::findClosestColorsInAllBooks('FF5500', limit: 2, algorithm: 'ciede2000');

    expect($closest->pluck('name')->all())->toBe(['Thread Orange', 'Ink Red'])
        ->and($closest->map(fn (Color $color): string => $color->colorBook->slug)->all())->toBe(['threads', 'inks']);
});

// REST API

test('find-closest endpoint searches all books', function (): void {
    $this->getJson('/api/laratone/find-closest?hex=FF5500&limit=2')
        ->assertStatus(200)
        ->assertJsonPath('target_hex', 'FF5500')
        ->assertJsonPath('algorithm', 'lab')
        ->assertJsonCount(2, 'matches')
        ->assertJsonPath('matches.0.name', 'Thread Orange')
        ->assertJsonPath('matches.0.color_book', ['name' => 'Threads', 'slug' => 'threads'])
        ->assertJsonPath('matches.1.color_book.slug', 'inks')
        ->assertJsonStructure(['matches' => [['color_book', 'name', 'hex', 'distance', 'rgb', 'cmyk', 'lab', 'oklch']]]);
});

test('find-closest endpoint accepts an algorithm', function (): void {
    $this->getJson('/api/laratone/find-closest?hex=%23ff5500&algorithm=oklch')
        ->assertStatus(200)
        ->assertJsonPath('target_hex', 'FF5500')
        ->assertJsonPath('algorithm', 'oklch')
        ->assertJsonPath('matches.0.name', 'Thread Orange');
});

test('find-closest endpoint validates its input', function (): void {
    $this->getJson('/api/laratone/find-closest?hex=nothex&algorithm=nope')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hex', 'algorithm']);
});

test('find-closest endpoint serves fresh results after the cache is cleared', function (): void {
    $this->getJson('/api/laratone/find-closest?hex=FF5500')
        ->assertJsonPath('matches.0.name', 'Thread Orange');

    Laratone::addColorToBook(ColorBook::where('slug', 'inks')->firstOrFail(), ['name' => 'Exact Orange', 'hex' => 'FF5500']);

    $this->getJson('/api/laratone/find-closest?hex=FF5500')
        ->assertJsonPath('matches.0.name', 'Exact Orange');
});

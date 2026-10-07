<?php

declare(strict_types=1);

use Daikazu\Laratone\Facades\Laratone;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

beforeEach(function (): void {
    $this->colorBook = ColorBook::create(['name' => 'Solid Coated', 'slug' => 'solid-coated']);

    foreach (['Orange 021 C' => 'FE5000', '185 C' => 'E4002B', '1585 C' => 'FF6A13', 'Process Blue C' => '0085CA'] as $name => $hex) {
        Color::create(['name' => $name, 'hex' => $hex, 'color_book_id' => $this->colorBook->id]);
    }

    $other = ColorBook::create(['name' => 'Other Book', 'slug' => 'other-book']);
    Color::create(['name' => '185 C Copy', 'hex' => 'E4002B', 'color_book_id' => $other->id]);
});

// Programmatic API

test('searches colors in a book by partial name', function (): void {
    $results = Laratone::searchColors($this->colorBook, '85');

    expect($results->pluck('name')->all())->toBe(['1585 C', '185 C']);
});

test('search is case-insensitive', function (): void {
    expect(Laratone::searchColors($this->colorBook, 'process blue')->pluck('name')->all())
        ->toBe(['Process Blue C']);
});

test('search only returns colors from the given book', function (): void {
    expect(Laratone::searchColors($this->colorBook, 'copy'))->toBeEmpty();
});

test('search respects the limit', function (): void {
    expect(Laratone::searchColors($this->colorBook, 'C', 2))->toHaveCount(2);
});

// REST API

test('search endpoint returns matching colors', function (): void {
    $this->getJson('/api/laratone/colorbook/solid-coated/search?q=orange')
        ->assertStatus(200)
        ->assertJsonPath('query', 'orange')
        ->assertJsonCount(1, 'matches')
        ->assertJsonPath('matches.0.name', 'Orange 021 C')
        ->assertJsonPath('matches.0.hex', 'FE5000')
        ->assertJsonStructure(['query', 'matches' => [['name', 'hex', 'rgb', 'cmyk', 'lab', 'oklch']]]);
});

test('search endpoint respects the limit parameter', function (): void {
    $this->getJson('/api/laratone/colorbook/solid-coated/search?q=C&limit=3')
        ->assertStatus(200)
        ->assertJsonCount(3, 'matches');
});

test('search endpoint requires a query', function (): void {
    $this->getJson('/api/laratone/colorbook/solid-coated/search')
        ->assertStatus(422)
        ->assertJsonValidationErrors('q');
});

test('search endpoint rejects a limit above the configured maximum', function (): void {
    config()->set('laratone.max_match_limit', 5);

    $this->getJson('/api/laratone/colorbook/solid-coated/search?q=C&limit=6')
        ->assertStatus(422)
        ->assertJsonValidationErrors('limit');
});

test('search endpoint returns 404 for an unknown color book', function (): void {
    $this->getJson('/api/laratone/colorbook/missing/search?q=185')
        ->assertStatus(404);
});

test('search endpoint returns an empty list when nothing matches', function (): void {
    $this->getJson('/api/laratone/colorbook/solid-coated/search?q=zzz')
        ->assertStatus(200)
        ->assertJsonCount(0, 'matches');
});

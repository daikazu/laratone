<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

// Basic API tests

test('can find closest color in color book', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF5500');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'target_hex',
            'algorithm',
            'matches' => [
                '*' => ['name', 'hex', 'distance', 'rgb', 'cmyk', 'lab', 'oklch'],
            ],
        ])
        ->assertJsonPath('target_hex', 'FF5500')
        ->assertJsonPath('algorithm', 'lab')
        ->assertJsonCount(1, 'matches')
        ->assertJsonPath('matches.0.name', 'Red');
});

test('can find multiple closest colors with limit parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Orange', 'hex' => 'FF5500', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Yellow', 'hex' => 'FFFF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Green', 'hex' => '00FF00', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF3300&limit=3');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'matches');

    // Verify distance values are present and in ascending order
    $distances = collect($response->json('matches'))->pluck('distance')->toArray();
    expect($distances[0])->toBeLessThanOrEqual($distances[1])
        ->and($distances[1])->toBeLessThanOrEqual($distances[2]);
});

test('can specify OKLCH algorithm', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF5500&algorithm=oklch');

    $response->assertStatus(200)
        ->assertJsonPath('algorithm', 'oklch')
        ->assertJsonPath('matches.0.name', 'Red');
});

// Hex format tests

test('accepts hex without hash prefix', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF0000');

    $response->assertStatus(200)
        ->assertJsonPath('target_hex', 'FF0000');
});

test('accepts hex with hash prefix', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=%23FF0000');

    $response->assertStatus(200)
        ->assertJsonPath('target_hex', 'FF0000');
});

test('normalizes lowercase hex to uppercase', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=ff0000');

    $response->assertStatus(200)
        ->assertJsonPath('target_hex', 'FF0000');
});

// Validation tests

test('returns 422 when hex is missing', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['hex']);
});

test('returns 422 for invalid hex format', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['hex']);
});

test('returns 422 for short hex code', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FFF');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['hex']);
});

test('returns 422 for invalid algorithm', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF0000&algorithm=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['algorithm']);
});

test('returns 422 for limit less than 1', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF0000&limit=0');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['limit']);
});

test('returns 422 for limit exceeding max', function (): void {
    config(['laratone.max_match_limit' => 10]);
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF0000&limit=20');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['limit']);
});

// Error handling tests

test('returns 404 for non-existent color book', function (): void {
    $response = $this->getJson('/api/laratone/colorbook/non-existent/find-closest?hex=FF0000');

    $response->assertStatus(404)
        ->assertJson(['message' => 'Color book not found']);
});

test('returns empty matches for color book with no colors', function (): void {
    $colorBook = ColorBook::create(['name' => 'Empty Book', 'slug' => 'empty-book']);

    $response = $this->getJson('/api/laratone/colorbook/empty-book/find-closest?hex=FF0000');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'matches');
});

// Response structure tests

test('response includes all color space values', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF0000');

    $response->assertStatus(200);

    $match = $response->json('matches.0');

    expect($match)->toHaveKey('name')
        ->and($match)->toHaveKey('hex')
        ->and($match)->toHaveKey('distance')
        ->and($match)->toHaveKey('rgb')
        ->and($match)->toHaveKey('cmyk')
        ->and($match)->toHaveKey('lab')
        ->and($match)->toHaveKey('oklch')
        ->and($match['rgb'])->toHaveKeys(['r', 'g', 'b'])
        ->and($match['cmyk'])->toHaveKeys(['c', 'm', 'y', 'k'])
        ->and($match['lab'])->toHaveKeys(['l', 'a', 'b'])
        ->and($match['oklch'])->toHaveKeys(['l', 'c', 'h']);
});

test('distance is a numeric value', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF5500');

    $response->assertStatus(200);

    $distance = $response->json('matches.0.distance');
    expect($distance)->toBeNumeric();
});

// Caching tests

test('caches find closest responses', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);

    // First request primes the cache
    $response1 = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF5500');
    $response1->assertStatus(200);

    // Second request should return the same result (from cache)
    $response2 = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF5500');
    $response2->assertStatus(200)
        ->assertJsonPath('matches.0.name', 'Red');

    // Responses should be identical
    expect($response1->json())->toBe($response2->json());
});

test('different parameters produce different cache keys', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test Book', 'slug' => 'test-book']);
    Color::create(['name' => 'Red', 'hex' => 'FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Blue', 'hex' => '0000FF', 'color_book_id' => $colorBook->id]);

    // Request with limit=1
    $response1 = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF0000&limit=1');
    $response1->assertJsonCount(1, 'matches');

    // Request with limit=2 should not use the cached limit=1 response
    $response2 = $this->getJson('/api/laratone/colorbook/test-book/find-closest?hex=FF0000&limit=2');
    $response2->assertJsonCount(2, 'matches');
});

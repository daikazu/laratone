<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

test('can get color book by slug', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    $color = Color::create([
        'name'          => 'Test Color',
        'hex'           => '#FF0000',
        'color_book_id' => $colorBook->id,
    ]);

    $response = $this->getJson('/api/laratone/colorbook/test-book');

    $response->assertStatus(200)
        ->assertJson([
            'name' => $colorBook->name,
            'slug' => $colorBook->slug,
        ])
        ->assertJsonPath('colors.0.name', $color->name);
});

test('returns 404 for non-existent color book', function (): void {
    $response = $this->getJson('/api/laratone/colorbook/non-existent');

    $response->assertStatus(404)
        ->assertJson(['message' => 'Color book not found']);
});

test('can get color book with limit parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    Color::create(['name' => 'Color 1', 'hex' => '#FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 2', 'hex' => '#00FF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 3', 'hex' => '#0000FF', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book?limit=2');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'colors');
});

test('can get color book with sort parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    Color::create(['name' => 'B Color', 'hex' => '#FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'A Color', 'hex' => '#00FF00', 'color_book_id' => $colorBook->id]);

    $response = $this->getJson('/api/laratone/colorbook/test-book?sort=asc');

    $response->assertStatus(200)
        ->assertJsonPath('colors.0.name', 'A Color')
        ->assertJsonPath('colors.1.name', 'B Color');
});

test('can get color book with random parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    Color::create(['name' => 'Color 1', 'hex' => '#FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 2', 'hex' => '#00FF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 3', 'hex' => '#0000FF', 'color_book_id' => $colorBook->id]);

    $response1 = $this->getJson('/api/laratone/colorbook/test-book?random=1');
    $response2 = $this->getJson('/api/laratone/colorbook/test-book?random=1');

    $response1->assertStatus(200);
    $response2->assertStatus(200);

    // Both should have 3 colors (order may differ due to random)
    expect($response1->json('colors'))->toHaveCount(3)
        ->and($response2->json('colors'))->toHaveCount(3);
});

test('can get all color books', function (): void {
    ColorBook::create(['name' => 'Book 1', 'slug' => 'book-1']);
    ColorBook::create(['name' => 'Book 2', 'slug' => 'book-2']);

    $response = $this->getJson('/api/laratone/colorbooks');

    $response->assertStatus(200)
        ->assertJsonCount(2);
});

test('can get color books with sort parameter', function (): void {
    ColorBook::create(['name' => 'B Book', 'slug' => 'b-book']);
    ColorBook::create(['name' => 'A Book', 'slug' => 'a-book']);

    $response = $this->getJson('/api/laratone/colorbooks?sort=asc');

    $response->assertStatus(200)
        ->assertJsonPath('0.name', 'A Book')
        ->assertJsonPath('1.name', 'B Book');
});

test('caches color book responses', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    Color::create([
        'name'          => 'Test Color',
        'hex'           => '#FF0000',
        'color_book_id' => $colorBook->id,
    ]);

    $response1 = $this->getJson('/api/laratone/colorbook/test-book');
    $response1->assertStatus(200);

    // Delete the color book from the database
    $colorBook->delete();

    // The response should still be cached
    $response2 = $this->getJson('/api/laratone/colorbook/test-book');

    $response2->assertStatus(200)
        ->assertJsonPath('name', 'Test book');
});

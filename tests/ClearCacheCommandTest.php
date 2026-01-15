<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Support\Facades\Cache;

test('clear cache command clears laratone cache', function (): void {
    // Create a color book to populate cache
    $colorBook = ColorBook::factory()->create();

    // Prime the cache by accessing the color book
    Cache::put('laratone.color_books', collect([$colorBook]), 3600);
    Cache::put("laratone.color_book.{$colorBook->slug}", $colorBook, 3600);
    Cache::put("laratone.color_book.{$colorBook->slug}.colors", collect(), 3600);

    // Verify cache is populated
    expect(Cache::has('laratone.color_books'))->toBeTrue();

    // Run the clear cache command
    $this->artisan('laratone:clear-cache')
        ->assertSuccessful()
        ->expectsOutput('Laratone cache cleared successfully.');

    // Verify cache is cleared
    expect(Cache::has('laratone.color_books'))->toBeFalse()
        ->and(Cache::has("laratone.color_book.{$colorBook->slug}"))->toBeFalse()
        ->and(Cache::has("laratone.color_book.{$colorBook->slug}.colors"))->toBeFalse();
});

test('clear cache command succeeds even when cache is empty', function (): void {
    $this->artisan('laratone:clear-cache')
        ->assertSuccessful()
        ->expectsOutput('Laratone cache cleared successfully.');
});

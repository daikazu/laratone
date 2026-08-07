<?php

declare(strict_types=1);

use Daikazu\Laratone\Laratone;
use Daikazu\Laratone\Models\ColorBook;

test('clear cache command clears laratone cache', function (): void {
    $colorBook = ColorBook::factory()->create();
    $laratone = new Laratone;

    // Prime the cache, then change data behind the cache's back
    $laratone->colorBooks();
    $laratone->colorBookBySlug($colorBook->slug);
    ColorBook::query()->delete();

    // Cached results are still served
    expect($laratone->colorBooks())->toHaveCount(1)
        ->and($laratone->colorBookBySlug($colorBook->slug))->not->toBeNull();

    // Run the clear cache command
    $this->artisan('laratone:clear-cache')
        ->assertSuccessful()
        ->expectsOutput('Laratone cache cleared successfully.');

    // Fresh data is served, including for the deleted book's slug
    expect($laratone->colorBooks())->toHaveCount(0)
        ->and($laratone->colorBookBySlug($colorBook->slug))->toBeNull();
});

test('clear cache command succeeds even when cache is empty', function (): void {
    $this->artisan('laratone:clear-cache')
        ->assertSuccessful()
        ->expectsOutput('Laratone cache cleared successfully.');
});

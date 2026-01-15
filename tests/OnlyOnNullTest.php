<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\ColorBook;

test('does not call only() on null when color book not found', function (): void {
    // Ensure no color books exist
    expect(ColorBook::all())->toHaveCount(0);

    // This should not throw an error "Call to a member function only() on null"
    $response = $this->getJson('/api/laratone/colorbook/non-existent-slug');

    $response->assertStatus(404)
        ->assertJson(['message' => 'Color book not found']);
});

test('does not call only() on null when color book not found with parameters', function (): void {
    // Ensure no color books exist
    expect(ColorBook::all())->toHaveCount(0);

    // This should not throw an error "Call to a member function only() on null"
    $response = $this->getJson('/api/laratone/colorbook/non-existent-slug?limit=10&sort=asc&random=1');

    $response->assertStatus(404)
        ->assertJson(['message' => 'Color book not found']);
});

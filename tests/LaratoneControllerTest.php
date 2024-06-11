<?php

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('migrate');
});

it('tests colorbook method', function () {
    // Arrange
    $colorBook = ColorBook::factory()->create(['slug' => 'test-book']);
    Color::factory()->count(5)->create(['color_book_id' => $colorBook->id]);

    // Act
    $response = $this->get('/api/laratone/colorbook/test-book');

    // Assert
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'name',
        'slug',
        'colors' => [
            '*' => [
                'name',
                'hex',
            ],
        ],
    ]);
});

it('tests colorbooks method', function () {
    // Arrange
    ColorBook::factory()->count(5)->create();

    // Act
    $response = $this->get('/api/laratone/colorbooks');

    // Assert
    $response->assertStatus(200);
    $response->assertJsonStructure([
        '*' => [
            'name',
            'slug',
        ],
    ]);
});

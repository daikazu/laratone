<?php

use Daikazu\Laratone\Http\Controllers\LaratoneController;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

beforeEach(function (): void {

    // Set up database configuration
    $this->app['config']->set('database.default', 'testing');
    $this->app['config']->set('database.connections.testing', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
    ]);

    // Set cart to use database storage
    $this->app['config']->set('laratone.storage', 'database');

    $this->loadMigrationsFrom(__DIR__ . '/fixtures/migrations');

    // Create the cart tables
    $this->artisan('migrate', ['--database' => 'testing']);

});

test('can get color book by slug', function () {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);

    $controller = new LaratoneController;
    $response = $controller->colorbook(request(), 'test-book');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getData()->name)->toBe($colorBook->name)
        ->and($response->getData()->colors[0]->name)->toBe($color->name);
});

test('returns 404 for non-existent color book', function () {
    $controller = new LaratoneController;
    $response = $controller->colorbook(request(), 'non-existent');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(404)
        ->and($response->getData()->message)->toBe('Color book not found');
});

test('can get color book with limit parameter', function () {
    $colorBook = ColorBook::factory()->create(['slug' => 'test-book']);
    Color::factory()->count(3)->create(['color_book_id' => $colorBook->id]);

    $request = request()->merge(['limit' => 2]);
    $controller = new LaratoneController;
    $response = $controller->colorbook($request, 'test-book');

    expect($response->getData()->colors)->toHaveCount(2);
});

test('can get color book with sort parameter', function () {
    $colorBook = ColorBook::factory()->create(['slug' => 'test-book']);
    Color::factory()->create(['color_book_id' => $colorBook->id, 'name' => 'B Color']);
    Color::factory()->create(['color_book_id' => $colorBook->id, 'name' => 'A Color']);

    $request = request()->merge(['sort' => 'asc']);
    $controller = new LaratoneController;
    $response = $controller->colorbook($request, 'test-book');

    expect($response->getData()->colors[0]->name)->toBe('A Color')
        ->and($response->getData()->colors[1]->name)->toBe('B Color');
});

test('can get color book with random parameter', function () {
    $colorBook = ColorBook::factory()->create(['slug' => 'test-book']);
    Color::factory()->count(3)->create(['color_book_id' => $colorBook->id]);

    $request = request()->merge(['random' => true]);
    $controller = new LaratoneController;
    $response1 = $controller->colorbook($request, 'test-book');
    $response2 = $controller->colorbook($request, 'test-book');

    // The order should be different between the two responses
    expect($response1->getData()->colors)->not->toBe($response2->getData()->colors);
});

test('can get all color books', function () {
    ColorBook::factory()->count(2)->create();

    $controller = new LaratoneController;
    $response = $controller->colorbooks(request());

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getData())->toHaveCount(2);
});

test('can get color books with sort parameter', function () {
    ColorBook::factory()->create(['name' => 'B Book']);
    ColorBook::factory()->create(['name' => 'A Book']);

    $request = request()->merge(['sort' => 'asc']);
    $controller = new LaratoneController;
    $response = $controller->colorbooks($request);

    expect($response->getData()[0]->name)->toBe('A Book')
        ->and($response->getData()[1]->name)->toBe('B Book');
});

test('caches color book responses', function () {
    $colorBook = ColorBook::factory()->create(['slug' => 'test-book']);
    $color = Color::factory()->create(['color_book_id' => $colorBook->id]);

    $controller = new LaratoneController;
    $response1 = $controller->colorbook(request(), 'test-book');

    // Delete the color book from the database
    $colorBook->delete();

    // The response should still be cached
    $response2 = $controller->colorbook(request(), 'test-book');

    expect($response2->getStatusCode())->toBe(200)
        ->and($response2->getData()->name)->toBe($colorBook->name);
});

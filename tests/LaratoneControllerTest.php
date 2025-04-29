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

test('can get color book by slug', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    $color = Color::create([
        'name'          => 'Test Color',
        'hex'           => '#FF0000',
        'color_book_id' => $colorBook->id,
    ]);

    $controller = new LaratoneController;
    $response = $controller->colorbook(request(), 'test-book');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getData()->name)->toBe($colorBook->name)
        ->and($response->getData()->colors[0]->name)->toBe($color->name);
});

test('returns 404 for non-existent color book', function (): void {
    $controller = new LaratoneController;
    $response = $controller->colorbook(request(), 'non-existent');

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(404)
        ->and($response->getData()->message)->toBe('Color book not found');
});

test('can get color book with limit parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    Color::create(['name' => 'Color 1', 'hex' => '#FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 2', 'hex' => '#00FF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 3', 'hex' => '#0000FF', 'color_book_id' => $colorBook->id]);

    $request = request()->merge(['limit' => 2]);
    $controller = new LaratoneController;
    $response = $controller->colorbook($request, 'test-book');

    expect($response->getData()->colors)->toHaveCount(2);
});

test('can get color book with sort parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    Color::create(['name' => 'B Color', 'hex' => '#FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'A Color', 'hex' => '#00FF00', 'color_book_id' => $colorBook->id]);

    $request = request()->merge(['sort' => 'asc']);
    $controller = new LaratoneController;
    $response = $controller->colorbook($request, 'test-book');

    expect($response->getData()->colors[0]->name)->toBe('A Color')
        ->and($response->getData()->colors[1]->name)->toBe('B Color');
});

test('can get color book with random parameter', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    Color::create(['name' => 'Color 1', 'hex' => '#FF0000', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 2', 'hex' => '#00FF00', 'color_book_id' => $colorBook->id]);
    Color::create(['name' => 'Color 3', 'hex' => '#0000FF', 'color_book_id' => $colorBook->id]);

    $request = request()->merge(['random' => true]);
    $controller = new LaratoneController;
    $response1 = $controller->colorbook($request, 'test-book');
    $response2 = $controller->colorbook($request, 'test-book');

    // The order should be different between the two responses
    expect($response1->getData()->colors)->not->toBe($response2->getData()->colors);
});

test('can get all color books', function (): void {
    ColorBook::create(['name' => 'Book 1', 'slug' => 'book-1']);
    ColorBook::create(['name' => 'Book 2', 'slug' => 'book-2']);

    $controller = new LaratoneController;
    $response = $controller->colorbooks(request());

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getData())->toHaveCount(2);
});

test('can get color books with sort parameter', function (): void {
    ColorBook::create(['name' => 'B Book', 'slug' => 'b-book']);
    ColorBook::create(['name' => 'A Book', 'slug' => 'a-book']);

    $request = request()->merge(['sort' => 'asc']);
    $controller = new LaratoneController;
    $response = $controller->colorbooks($request);

    expect($response->getData()[0]->name)->toBe('A Book')
        ->and($response->getData()[1]->name)->toBe('B Book');
});

test('caches color book responses', function (): void {
    $colorBook = ColorBook::create(['name' => 'Test book', 'slug' => 'test-book']);
    $color = Color::create([
        'name'          => 'Test Color',
        'hex'           => '#FF0000',
        'color_book_id' => $colorBook->id,
    ]);

    $controller = new LaratoneController;
    $response1 = $controller->colorbook(request(), 'test-book');

    // Delete the color book from the database
    $colorBook->delete();

    // The response should still be cached
    $response2 = $controller->colorbook(request(), 'test-book');

    expect($response2->getStatusCode())->toBe(200)
        ->and($response2->getData()->name)->toBe($colorBook->name);
});

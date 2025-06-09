<?php

use Daikazu\Laratone\Http\Controllers\LaratoneController;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    // Set up database configuration
    $this->app['config']->set('database.default', 'testing');
    $this->app['config']->set('database.connections.testing', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
    ]);

    $this->app['config']->set('laratone.storage', 'database');
    $this->loadMigrationsFrom(__DIR__ . '/fixtures/migrations');
    $this->artisan('migrate', ['--database' => 'testing']);
    Cache::flush();
});

test('does not call only() on null when color book not found', function (): void {
    // Ensure no color books exist
    $this->assertCount(0, ColorBook::all());

    // Create controller
    $controller = new LaratoneController;

    // This should not throw an error "Call to a member function only() on null"
    $response = $controller->colorbook(request(), 'non-existent-slug');

    // Verify response
    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(404)
        ->and($response->getData()->message)->toBe('Color book not found');
});

test('does not call only() on null when color book not found with parameters', function (): void {
    // Ensure no color books exist
    $this->assertCount(0, ColorBook::all());

    // Create request with parameters
    $request = request()->merge([
        'limit'  => 10,
        'sort'   => 'asc',
        'random' => true,
    ]);

    // Create controller
    $controller = new LaratoneController;

    // This should not throw an error "Call to a member function only() on null"
    $response = $controller->colorbook($request, 'non-existent-slug');

    // Verify response
    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getStatusCode())->toBe(404)
        ->and($response->getData()->message)->toBe('Color book not found');
});

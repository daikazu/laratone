<?php

use Daikazu\Laratone\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)
    ->in(__DIR__);

// beforeEach(function (): void {
//
//    // Set up database configuration
//    $this->app['config']->set('database.default', 'testing');
//    $this->app['config']->set('database.connections.testing', [
//        'driver'   => 'sqlite',
//        'database' => ':memory:',
//        'prefix'   => '',
//    ]);
//
//    // Set cart to use database storage
//    $this->app['config']->set('laratone.storage', 'database');
//
//    $this->loadMigrationsFrom(__DIR__ . '/fixtures/migrations');
//
//    // Create the cart tables
//    $this->artisan('migrate', ['--database' => 'testing']);
//
// });

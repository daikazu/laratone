<?php

declare(strict_types=1);

use Daikazu\Laratone\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function (): void {
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
    })
    ->in(__DIR__);

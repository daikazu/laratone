<?php

namespace Daikazu\Laratone\Tests;

use Daikazu\Laratone\Facades\Laratone;
use Daikazu\Laratone\ServiceProvider;
use Orchestra\Testbench\TestCase;

class LaratoneTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'laratone' => Laratone::class,
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}

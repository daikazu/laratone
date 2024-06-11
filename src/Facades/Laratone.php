<?php

namespace Daikazu\Laratone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Daikazu\Laratone\Laratone
 */
class Laratone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Daikazu\Laratone\Laratone::class;
    }
}

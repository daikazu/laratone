<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Daikazu\Laratone\Laratone
 */
final class Laratone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Daikazu\Laratone\Laratone::class;
    }
}

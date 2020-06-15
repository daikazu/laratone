<?php

namespace Daikazu\Laratone\Facades;

use Illuminate\Support\Facades\Facade;

class Laratone extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laratone';
    }
}

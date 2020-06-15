<?php

namespace Daikazu\Laratone;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;

class Laratone
{

    public function colorbooks()
    {
        return ColorBook::with('colors')->get();
    }

    public function colorBookBySlug($colorBookSlug)
    {
        return ColorBook::slug($colorBookSlug)->first();
    }


}

<?php

namespace Daikazu\Laratone;

use Daikazu\Laratone\Models\ColorBook;

class Laratone
{
    public function colorBooks(): \Illuminate\Database\Eloquent\Collection|array
    {
        return ColorBook::with('colors')->get();
    }

    public function colorBookBySlug($colorBookSlug)
    {
        return ColorBook::slug($colorBookSlug)->first();
    }
}

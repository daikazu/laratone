<?php

namespace Daikazu\Laratone\Faker;

use Faker\Provider\Base;

class ColorFakerProvider extends Base
{
    public function labColor(): string
    {
        $l = mt_rand(0, 100);
        $a = mt_rand(-128, 127);
        $b = mt_rand(-128, 127);

        return "{$l},{$a},{$b}";
    }

    public function cmykColor(): string
    {
        $c = mt_rand(0, 100);
        $m = mt_rand(0, 100);
        $y = mt_rand(0, 100);
        $k = mt_rand(0, 100);

        return "{$c},{$m},{$y},{$k}";
    }
}

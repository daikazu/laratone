<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Faker;

use Faker\Provider\Base;

final class ColorFakerProvider extends Base
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

    public function oklchColor(): string
    {
        $l = round(mt_rand(0, 100) / 100, 4);
        $c = round(mt_rand(0, 37) / 100, 4);
        $h = round(mt_rand(0, 36000) / 100, 2);

        return "{$l},{$c},{$h}";
    }
}

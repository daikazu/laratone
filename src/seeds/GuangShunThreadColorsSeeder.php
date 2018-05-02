<?php

namespace Daikazu\Laratone\Colorbooks;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\Colorbook;
use Illuminate\Database\Seeder;

class GuangShunThreadColorsSeeder extends Seeder
{


    private $colors;

    public function __construct()
    {
        $this->colors = json_decode(file_get_contents(__DIR__ . '/colorbooks/GuangShunThreadColors.json'));
    }


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $colorbook = new Colorbook();
        $colorbook->name = $this->colors->name;
        $colorbook->save();

        array_map(function ($value) use ($colorbook) {

            $color = new Color();
            $color->colorbook_id = $colorbook->id;
            $color->name = $value->name;
            $color->hex = $value->hex;
            $color->save();

        }, $this->colors->data);


    }
}

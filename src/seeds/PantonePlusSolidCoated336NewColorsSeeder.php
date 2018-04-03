<?php

namespace Daikazu\Laratone\Colorbooks;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\Colorbook;
use Illuminate\Database\Seeder;

class PantonePlusSolidCoated336NewColorsSeeder extends Seeder
{


    private $colors;

    public function __construct()
    {
        $this->colors = json_decode(file_get_contents(__DIR__ . '/colorbooks/PatonePlusSolidCoated336NewColors.json'));
    }


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        $colorbook = new Colorbook([
            'name' => $this->colors->name,
        ]);
        $colorbook = $colorbook->save();


        array_map(function ($value) {

            $color = new Color([
                'name' => $value->name,
                'lab'  => $value->lab,
                'hex'  => $value->hex,
                'rgb'  => $value->rgb,
                'cmyk' => $value->cmyk,
            ]);
            $color->save();

        }, $this->colors->data);


    }
}

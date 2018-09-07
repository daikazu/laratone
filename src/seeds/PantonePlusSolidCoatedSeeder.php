<?php

namespace Daikazu\Laratone\ColorBooks;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\Colorbook;
use Illuminate\Database\Seeder;

class PantonePlusSolidCoatedSeeder extends Seeder
{
    private $colors;

    public function __construct()
    {
        $this->colors = json_decode(file_get_contents(__DIR__.'/colorbooks/PantonePlusSolidCoated.json'));
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $colorBook = new ColorBook();
        $colorBook->name = $this->colors->name;
        $colorBook->save();

        array_map(function ($value) use ($colorBook) {
            $color = new Color();
            $color->color_book_id = $colorBook->id;
            $color->name = $value->name;
            $color->hex = $value->hex;
            $color->save();
        }, $this->colors->data);
    }
}

<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Database\Seeders;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Daikazu\Laratone\Services\ColorConverter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $converter = app(ColorConverter::class);

        $colorBooks = [
            [
                'name'   => 'Autumn Palette',
                'colors' => [
                    ['name' => 'Pumpkin Spice', 'hex' => '#D35400'],
                    ['name' => 'Crimson Maple', 'hex' => '#8B0000'],
                    ['name' => 'Golden Harvest', 'hex' => '#DAA520'],
                    ['name' => 'Rustic Brown', 'hex' => '#8B4513'],
                    ['name' => 'Amber Glow', 'hex' => '#FFBF00'],
                ],
            ],
            [
                'name'   => 'Ocean Blues',
                'colors' => [
                    ['name' => 'Deep Sea', 'hex' => '#00008B'],
                    ['name' => 'Turquoise Wave', 'hex' => '#40E0D0'],
                    ['name' => 'Aqua Marine', 'hex' => '#7FFFD4'],
                    ['name' => 'Navy Blue', 'hex' => '#000080'],
                    ['name' => 'Sky Blue', 'hex' => '#87CEEB'],
                ],
            ],
            [
                'name'   => 'Forest Greens',
                'colors' => [
                    ['name' => 'Emerald', 'hex' => '#50C878'],
                    ['name' => 'Forest Green', 'hex' => '#228B22'],
                    ['name' => 'Olive', 'hex' => '#808000'],
                    ['name' => 'Sage', 'hex' => '#BCB88A'],
                    ['name' => 'Moss', 'hex' => '#8A9A5B'],
                ],
            ],
        ];

        foreach ($colorBooks as $colorBookData) {
            $colorBook = ColorBook::create([
                'name' => $colorBookData['name'],
                'slug' => Str::slug($colorBookData['name']),
            ]);

            foreach ($colorBookData['colors'] as $colorData) {
                $rgb = $converter->hexToRgb($colorData['hex']);

                Color::create([
                    'color_book_id' => $colorBook->id,
                    'name'          => $colorData['name'],
                    'hex'           => ltrim($colorData['hex'], '#'),
                    'rgb'           => $rgb,
                    'lab'           => $converter->rgbToLab($rgb),
                    'cmyk'          => $converter->rgbToCmyk($rgb),
                    'oklch'         => $converter->rgbToOklch($rgb),
                ]);
            }
        }
    }
}

<?php

namespace Daikazu\Laratone\Database\Seeders;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        // Create a few themed color books
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
                'slug' => \Illuminate\Support\Str::slug($colorBookData['name']),
            ]);

            foreach ($colorBookData['colors'] as $colorData) {
                // Convert hex to RGB
                $rgb = $this->hexToRgb($colorData['hex']);

                Color::create([
                    'color_book_id' => $colorBook->id,
                    'name'          => $colorData['name'],
                    'hex'           => $colorData['hex'],
                    'rgb'           => implode(',', $rgb),
                    'lab'           => $this->rgbToLab($rgb),
                    'cmyk'          => $this->rgbToCmyk($rgb),
                ]);
            }
        }
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function rgbToLab(array $rgb): string
    {
        // Simple conversion for testing purposes
        // In a real application, you'd want to use a proper color conversion library
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;

        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;

        return sprintf('%.2f,%.2f,%.2f', $x * 100, $y * 100, $z * 100);
    }

    private function rgbToCmyk(array $rgb): string
    {
        // Simple conversion for testing purposes
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;

        $k = 1 - max($r, $g, $b);
        $c = (1 - $r - $k) / (1 - $k);
        $m = (1 - $g - $k) / (1 - $k);
        $y = (1 - $b - $k) / (1 - $k);

        return sprintf('%.2f,%.2f,%.2f,%.2f', $c * 100, $m * 100, $y * 100, $k * 100);
    }
}

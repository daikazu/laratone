<?php

declare(strict_types=1);

use Daikazu\Laratone\Database\Seeders\ColorSeeder;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Services\ColorConverter;

test('color seeder stores real LAB and OKLCH values', function (): void {
    (new ColorSeeder)->run();

    $color = Color::where('name', 'Crimson Maple')->first();
    $converter = new ColorConverter;
    $expectedLab = $converter->rgbToLab($converter->hexToRgb('8B0000'));
    $expectedOklch = $converter->rgbToOklch($converter->hexToRgb('8B0000'));

    expect($color)->not->toBeNull()
        ->and($color->lab['l'])->toEqualWithDelta($expectedLab['l'], 0.01)
        ->and($color->lab['a'])->toEqualWithDelta($expectedLab['a'], 0.01)
        ->and($color->lab['b'])->toEqualWithDelta($expectedLab['b'], 0.01)
        ->and($color->oklch['l'])->toEqualWithDelta($expectedOklch['l'], 0.001)
        ->and($color->oklch['h'])->toEqualWithDelta($expectedOklch['h'], 0.01);
});

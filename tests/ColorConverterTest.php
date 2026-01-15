<?php

declare(strict_types=1);

use Daikazu\Laratone\Services\ColorConverter;

// Hex to RGB Tests

test('converts hex to rgb for red', function (): void {
    $converter = new ColorConverter;

    expect($converter->hexToRgb('FF0000'))->toBe(['r' => 255, 'g' => 0, 'b' => 0]);
});

test('converts hex to rgb for green', function (): void {
    $converter = new ColorConverter;

    expect($converter->hexToRgb('00FF00'))->toBe(['r' => 0, 'g' => 255, 'b' => 0]);
});

test('converts hex to rgb for blue', function (): void {
    $converter = new ColorConverter;

    expect($converter->hexToRgb('0000FF'))->toBe(['r' => 0, 'g' => 0, 'b' => 255]);
});

test('converts hex to rgb for white', function (): void {
    $converter = new ColorConverter;

    expect($converter->hexToRgb('FFFFFF'))->toBe(['r' => 255, 'g' => 255, 'b' => 255]);
});

test('converts hex to rgb for black', function (): void {
    $converter = new ColorConverter;

    expect($converter->hexToRgb('000000'))->toBe(['r' => 0, 'g' => 0, 'b' => 0]);
});

test('handles hex with hash prefix', function (): void {
    $converter = new ColorConverter;

    expect($converter->hexToRgb('#FF5500'))->toBe(['r' => 255, 'g' => 85, 'b' => 0]);
});

test('handles lowercase hex', function (): void {
    $converter = new ColorConverter;

    expect($converter->hexToRgb('ff5500'))->toBe(['r' => 255, 'g' => 85, 'b' => 0]);
});

test('throws exception for invalid hex length', function (): void {
    $converter = new ColorConverter;

    $converter->hexToRgb('FFF');
})->throws(InvalidArgumentException::class, 'Invalid hex color');

test('throws exception for invalid hex characters', function (): void {
    $converter = new ColorConverter;

    $converter->hexToRgb('GGGGGG');
})->throws(InvalidArgumentException::class, 'non-hex characters');

// RGB to Hex Tests

test('converts rgb to hex', function (): void {
    $converter = new ColorConverter;

    expect($converter->rgbToHex(['r' => 255, 'g' => 85, 'b' => 0]))->toBe('FF5500');
});

test('converts rgb to hex for black', function (): void {
    $converter = new ColorConverter;

    expect($converter->rgbToHex(['r' => 0, 'g' => 0, 'b' => 0]))->toBe('000000');
});

test('clamps out of range rgb values', function (): void {
    $converter = new ColorConverter;

    expect($converter->rgbToHex(['r' => 300, 'g' => -10, 'b' => 128]))->toBe('FF0080');
});

// RGB to CMYK Tests

test('converts rgb to cmyk for red', function (): void {
    $converter = new ColorConverter;

    expect($converter->rgbToCmyk(['r' => 255, 'g' => 0, 'b' => 0]))
        ->toBe(['c' => 0, 'm' => 100, 'y' => 100, 'k' => 0]);
});

test('converts rgb to cmyk for cyan', function (): void {
    $converter = new ColorConverter;

    expect($converter->rgbToCmyk(['r' => 0, 'g' => 255, 'b' => 255]))
        ->toBe(['c' => 100, 'm' => 0, 'y' => 0, 'k' => 0]);
});

test('converts rgb to cmyk for black', function (): void {
    $converter = new ColorConverter;

    expect($converter->rgbToCmyk(['r' => 0, 'g' => 0, 'b' => 0]))
        ->toBe(['c' => 0, 'm' => 0, 'y' => 0, 'k' => 100]);
});

test('converts rgb to cmyk for white', function (): void {
    $converter = new ColorConverter;

    expect($converter->rgbToCmyk(['r' => 255, 'g' => 255, 'b' => 255]))
        ->toBe(['c' => 0, 'm' => 0, 'y' => 0, 'k' => 0]);
});

test('converts rgb to cmyk for orange', function (): void {
    $converter = new ColorConverter;

    // RGB(255, 85, 0) should be approximately C=0, M=67, Y=100, K=0
    $result = $converter->rgbToCmyk(['r' => 255, 'g' => 85, 'b' => 0]);

    expect($result['c'])->toBe(0)
        ->and($result['m'])->toBe(67)
        ->and($result['y'])->toBe(100)
        ->and($result['k'])->toBe(0);
});

// RGB to LAB Tests

test('converts rgb to lab for white with D65', function (): void {
    $converter = new ColorConverter;

    $result = $converter->rgbToLab(['r' => 255, 'g' => 255, 'b' => 255], 'D65');

    // White should be approximately L=100, a=0, b=0
    expect($result['l'])->toBeGreaterThan(99)
        ->and(abs($result['a']))->toBeLessThan(1)
        ->and(abs($result['b']))->toBeLessThan(1);
});

test('converts rgb to lab for black', function (): void {
    $converter = new ColorConverter;

    $result = $converter->rgbToLab(['r' => 0, 'g' => 0, 'b' => 0], 'D65');

    // Black should be L=0, a=0, b=0
    expect($result['l'])->toBe(0.0)
        ->and($result['a'])->toBe(0.0)
        ->and($result['b'])->toBe(0.0);
});

test('converts rgb to lab for red', function (): void {
    $converter = new ColorConverter;

    $result = $converter->rgbToLab(['r' => 255, 'g' => 0, 'b' => 0], 'D65');

    // Red should have high L, positive a (red-green axis), positive b
    expect($result['l'])->toBeGreaterThan(50)
        ->and($result['a'])->toBeGreaterThan(60); // Red has high positive a
});

test('converts rgb to lab for green', function (): void {
    $converter = new ColorConverter;

    $result = $converter->rgbToLab(['r' => 0, 'g' => 255, 'b' => 0], 'D65');

    // Green should have high L, negative a (green direction)
    expect($result['l'])->toBeGreaterThan(80)
        ->and($result['a'])->toBeLessThan(-80); // Green has large negative a
});

test('converts rgb to lab for blue', function (): void {
    $converter = new ColorConverter;

    $result = $converter->rgbToLab(['r' => 0, 'g' => 0, 'b' => 255], 'D65');

    // Blue should have lower L, and large negative b (blue direction)
    expect($result['l'])->toBeLessThan(40)
        ->and($result['b'])->toBeLessThan(-100); // Blue has large negative b
});

// White Point Tests

test('different white points produce different lab values', function (): void {
    $converter = new ColorConverter;
    $rgb = ['r' => 200, 'g' => 150, 'b' => 100];

    $labD50 = $converter->rgbToLab($rgb, 'D50');
    $labD65 = $converter->rgbToLab($rgb, 'D65');

    // Values should be different (especially b value due to blue channel difference)
    expect($labD50)->not->toBe($labD65);
});

test('throws exception for unknown white point', function (): void {
    $converter = new ColorConverter;

    $converter->rgbToLab(['r' => 255, 'g' => 0, 'b' => 0], 'INVALID');
})->throws(InvalidArgumentException::class, 'Unknown white point');

test('available white points returns valid options', function (): void {
    $whitePoints = ColorConverter::availableWhitePoints();

    expect($whitePoints)->toContain('D50')
        ->and($whitePoints)->toContain('D55')
        ->and($whitePoints)->toContain('D65')
        ->and($whitePoints)->toContain('D75');
});

// Integration: Full conversion chain

test('hex converts through all color spaces', function (): void {
    $converter = new ColorConverter;

    // Start with orange hex
    $rgb = $converter->hexToRgb('FF5500');
    $cmyk = $converter->rgbToCmyk($rgb);
    $lab = $converter->rgbToLab($rgb, 'D65');

    expect($rgb)->toBe(['r' => 255, 'g' => 85, 'b' => 0])
        ->and($cmyk['c'])->toBe(0)
        ->and($cmyk['k'])->toBe(0)
        ->and($lab['l'])->toBeGreaterThan(50);
});

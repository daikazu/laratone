<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Services;

use InvalidArgumentException;

/**
 * Service for converting between color spaces (Hex, RGB, CMYK, LAB, OKLCH).
 *
 * Uses sRGB color space with configurable white point references for LAB conversions.
 * OKLCH is a perceptually uniform color space that doesn't require white point configuration.
 */
final readonly class ColorConverter
{
    /**
     * Standard illuminant white point reference values (XYZ tristimulus).
     * Values are scaled to Y = 100.
     *
     * @var array<string, array{x: float, y: float, z: float}>
     */
    private const array WHITE_POINTS = [
        'D50' => ['x' => 96.422, 'y' => 100.000, 'z' => 82.521],   // Print/graphic arts
        'D55' => ['x' => 95.682, 'y' => 100.000, 'z' => 92.149],   // Mid-morning/afternoon
        'D65' => ['x' => 95.047, 'y' => 100.000, 'z' => 108.883],  // Daylight (standard)
        'D75' => ['x' => 94.972, 'y' => 100.000, 'z' => 122.638],  // North sky daylight
    ];

    /**
     * sRGB to XYZ transformation matrix (D65 reference).
     *
     * @var array<array<float>>
     */
    private const array SRGB_TO_XYZ_MATRIX = [
        [0.4124564, 0.3575761, 0.1804375],
        [0.2126729, 0.7151522, 0.0721750],
        [0.0193339, 0.1191920, 0.9503041],
    ];

    /**
     * Linear RGB to LMS transformation matrix for OKLab.
     *
     * @var array<array<float>>
     */
    private const array LINEAR_RGB_TO_LMS_MATRIX = [
        [0.4122214708, 0.5363325363, 0.0514459929],
        [0.2119034982, 0.6806995451, 0.1073969566],
        [0.0883024619, 0.2817188376, 0.6299787005],
    ];

    /**
     * LMS (cube root) to OKLab transformation matrix.
     *
     * @var array<array<float>>
     */
    private const array LMS_TO_OKLAB_MATRIX = [
        [0.2104542553, 0.7936177850, -0.0040720468],
        [1.9779984951, -2.4285922050, 0.4505937099],
        [0.0259040371, 0.7827717662, -0.8086757660],
    ];

    /**
     * Convert hex color code to RGB array.
     *
     * @return array{r: int, g: int, b: int}
     */
    public function hexToRgb(string $hex): array
    {
        // Remove # if present and convert to uppercase
        $hex = strtoupper(ltrim($hex, '#'));

        // Validate hex length
        if (strlen($hex) !== 6) {
            throw new InvalidArgumentException("Invalid hex color: {$hex}. Expected 6 characters.");
        }

        // Validate hex characters
        if (! ctype_xdigit($hex)) {
            throw new InvalidArgumentException("Invalid hex color: {$hex}. Contains non-hex characters.");
        }

        return [
            'r' => (int) hexdec(substr($hex, 0, 2)),
            'g' => (int) hexdec(substr($hex, 2, 2)),
            'b' => (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Convert RGB array to hex color code.
     *
     * @param  array{r: int, g: int, b: int}  $rgb
     */
    public function rgbToHex(array $rgb): string
    {
        return strtoupper(sprintf(
            '%02X%02X%02X',
            max(0, min(255, $rgb['r'])),
            max(0, min(255, $rgb['g'])),
            max(0, min(255, $rgb['b']))
        ));
    }

    /**
     * Convert RGB to CMYK.
     *
     * @param  array{r: int, g: int, b: int}  $rgb
     * @return array{c: int, m: int, y: int, k: int}
     */
    public function rgbToCmyk(array $rgb): array
    {
        // Normalize RGB to 0-1 range
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        // Calculate K (black key)
        $k = 1 - max($r, $g, $b);

        // Handle pure black (or very close to it to avoid division by zero)
        if ($k >= 0.9999) {
            return ['c' => 0, 'm' => 0, 'y' => 0, 'k' => 100];
        }

        // Calculate CMY
        $c = (1 - $r - $k) / (1 - $k);
        $m = (1 - $g - $k) / (1 - $k);
        $y = (1 - $b - $k) / (1 - $k);

        // Convert to percentages (0-100) and round
        return [
            'c' => (int) round($c * 100),
            'm' => (int) round($m * 100),
            'y' => (int) round($y * 100),
            'k' => (int) round($k * 100),
        ];
    }

    /**
     * Convert RGB to LAB color space.
     *
     * @param  array{r: int, g: int, b: int}  $rgb
     * @return array{l: float, a: float, b: float}
     */
    public function rgbToLab(array $rgb, string $whitePoint = 'D65'): array
    {
        if (! isset(self::WHITE_POINTS[$whitePoint])) {
            throw new InvalidArgumentException("Unknown white point: {$whitePoint}. Valid options: " . implode(', ', array_keys(self::WHITE_POINTS)));
        }

        $xyz = $this->rgbToXyz($rgb);
        $wp = self::WHITE_POINTS[$whitePoint];

        return $this->xyzToLab($xyz, $wp);
    }

    /**
     * Convert RGB to OKLCH color space.
     *
     * OKLCH is a perceptually uniform color space based on OKLab.
     * Unlike LAB, it doesn't require white point configuration.
     *
     * @param  array{r: int, g: int, b: int}  $rgb
     * @return array{l: float, c: float, h: float}
     */
    public function rgbToOklch(array $rgb): array
    {
        // RGB (0-255) → Linear RGB (0-1) → LMS → OKLab → OKLCH
        $linearRgb = $this->rgbToLinearRgb($rgb);
        $lms = $this->linearRgbToLms($linearRgb);
        $oklab = $this->lmsToOklab($lms);

        return $this->oklabToOklch($oklab);
    }

    /**
     * Convert RGB to linear RGB (apply inverse gamma).
     *
     * @param  array{r: int, g: int, b: int}  $rgb
     * @return array{r: float, g: float, b: float}
     */
    private function rgbToLinearRgb(array $rgb): array
    {
        return [
            'r' => $this->inverseGamma($rgb['r'] / 255),
            'g' => $this->inverseGamma($rgb['g'] / 255),
            'b' => $this->inverseGamma($rgb['b'] / 255),
        ];
    }

    /**
     * Convert linear RGB to LMS cone response.
     *
     * @param  array{r: float, g: float, b: float}  $linearRgb
     * @return array{l: float, m: float, s: float}
     */
    private function linearRgbToLms(array $linearRgb): array
    {
        $matrix = self::LINEAR_RGB_TO_LMS_MATRIX;
        $r = $linearRgb['r'];
        $g = $linearRgb['g'];
        $b = $linearRgb['b'];

        return [
            'l' => $matrix[0][0] * $r + $matrix[0][1] * $g + $matrix[0][2] * $b,
            'm' => $matrix[1][0] * $r + $matrix[1][1] * $g + $matrix[1][2] * $b,
            's' => $matrix[2][0] * $r + $matrix[2][1] * $g + $matrix[2][2] * $b,
        ];
    }

    /**
     * Convert LMS to OKLab.
     *
     * @param  array{l: float, m: float, s: float}  $lms
     * @return array{l: float, a: float, b: float}
     */
    private function lmsToOklab(array $lms): array
    {
        // Apply cube root to LMS values
        $l = $lms['l'] >= 0 ? $lms['l'] ** (1 / 3) : -((-$lms['l']) ** (1 / 3));
        $m = $lms['m'] >= 0 ? $lms['m'] ** (1 / 3) : -((-$lms['m']) ** (1 / 3));
        $s = $lms['s'] >= 0 ? $lms['s'] ** (1 / 3) : -((-$lms['s']) ** (1 / 3));

        $matrix = self::LMS_TO_OKLAB_MATRIX;

        return [
            'l' => $matrix[0][0] * $l + $matrix[0][1] * $m + $matrix[0][2] * $s,
            'a' => $matrix[1][0] * $l + $matrix[1][1] * $m + $matrix[1][2] * $s,
            'b' => $matrix[2][0] * $l + $matrix[2][1] * $m + $matrix[2][2] * $s,
        ];
    }

    /**
     * Convert OKLab to OKLCH (polar form).
     *
     * @param  array{l: float, a: float, b: float}  $oklab
     * @return array{l: float, c: float, h: float}
     */
    private function oklabToOklch(array $oklab): array
    {
        $l = $oklab['l'];
        $a = $oklab['a'];
        $b = $oklab['b'];

        // Chroma: distance from neutral axis
        $c = sqrt($a * $a + $b * $b);

        // Hue: angle in degrees (0-360)
        $h = atan2($b, $a) * (180 / M_PI);
        if ($h < 0) {
            $h += 360;
        }

        // For achromatic colors (c ≈ 0), hue is undefined - use 0
        if ($c < 0.0001) {
            $h = 0.0;
        }

        return [
            'l' => round($l, 4),
            'c' => round($c, 4),
            'h' => round($h, 2),
        ];
    }

    /**
     * Convert RGB to XYZ color space.
     *
     * @param  array{r: int, g: int, b: int}  $rgb
     * @return array{x: float, y: float, z: float}
     */
    public function rgbToXyz(array $rgb): array
    {
        // Normalize RGB to 0-1 and apply inverse sRGB companding (gamma correction)
        $r = $this->inverseGamma($rgb['r'] / 255);
        $g = $this->inverseGamma($rgb['g'] / 255);
        $b = $this->inverseGamma($rgb['b'] / 255);

        // Apply transformation matrix
        $matrix = self::SRGB_TO_XYZ_MATRIX;

        return [
            'x' => ($matrix[0][0] * $r + $matrix[0][1] * $g + $matrix[0][2] * $b) * 100,
            'y' => ($matrix[1][0] * $r + $matrix[1][1] * $g + $matrix[1][2] * $b) * 100,
            'z' => ($matrix[2][0] * $r + $matrix[2][1] * $g + $matrix[2][2] * $b) * 100,
        ];
    }

    /**
     * Convert XYZ to LAB color space.
     *
     * @param  array{x: float, y: float, z: float}  $xyz
     * @param  array{x: float, y: float, z: float}  $whitePoint
     * @return array{l: float, a: float, b: float}
     */
    private function xyzToLab(array $xyz, array $whitePoint): array
    {
        // Normalize XYZ by white point
        $x = $this->labFunction($xyz['x'] / $whitePoint['x']);
        $y = $this->labFunction($xyz['y'] / $whitePoint['y']);
        $z = $this->labFunction($xyz['z'] / $whitePoint['z']);

        // Calculate LAB values
        $l = (116 * $y) - 16;
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);

        return [
            'l' => round($l, 2),
            'a' => round($a, 2),
            'b' => round($b, 2),
        ];
    }

    /**
     * Apply inverse sRGB gamma correction (companding).
     */
    private function inverseGamma(float $value): float
    {
        if ($value <= 0.04045) {
            return $value / 12.92;
        }

        return (($value + 0.055) / 1.055) ** 2.4;
    }

    /**
     * LAB color space function (f function).
     */
    private function labFunction(float $t): float
    {
        // CIE standard: 6/29 cubed ≈ 0.008856, 29/3 squared ≈ 903.3
        $delta = 6 / 29;

        if ($t > $delta ** 3) {
            return $t ** (1 / 3);
        }

        return ($t / (3 * $delta ** 2)) + (4 / 29);
    }

    /**
     * Get available white point options.
     *
     * @return array<string>
     */
    public static function availableWhitePoints(): array
    {
        return array_keys(self::WHITE_POINTS);
    }
}

<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Services;

use Daikazu\Laratone\Models\Color;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Service for finding closest matching colors from a color book.
 *
 * Supports LAB (CIE76 Delta E), CIEDE2000 (Delta E 2000) and OKLCH
 * distance algorithms for perceptually accurate color matching.
 */
final readonly class ColorMatcher
{
    public const string ALGORITHM_LAB = 'lab';

    public const string ALGORITHM_OKLCH = 'oklch';

    public const string ALGORITHM_CIEDE2000 = 'ciede2000';

    private const array VALID_ALGORITHMS = [self::ALGORITHM_LAB, self::ALGORITHM_OKLCH, self::ALGORITHM_CIEDE2000];

    public function __construct(
        private ColorConverter $colorConverter
    ) {}

    /**
     * Find the closest matching colors from a collection.
     *
     * @param  string  $targetHex  The target color as a 6-character hex code (with or without #)
     * @param  Collection<int, Color>|\Illuminate\Database\Eloquent\Collection<int, Color>  $colors  The collection of colors to search
     * @param  int  $limit  Maximum number of matches to return (default: 1)
     * @param  string  $algorithm  Distance algorithm: 'lab', 'oklch' or 'ciede2000' (default: 'lab')
     * @return Collection<int, Color> Colors sorted by distance (closest first), with 'distance' attribute
     *
     * @throws InvalidArgumentException If algorithm is invalid
     */
    public function findClosest(
        string $targetHex,
        Collection|\Illuminate\Database\Eloquent\Collection $colors,
        int $limit = 1,
        string $algorithm = self::ALGORITHM_LAB
    ): Collection {
        $this->validateAlgorithm($algorithm);

        if ($colors->isEmpty()) {
            return new Collection;
        }

        // Convert target hex to the appropriate color space
        $targetRgb = $this->colorConverter->hexToRgb($targetHex);
        $targetColorSpace = $algorithm === self::ALGORITHM_OKLCH
            ? $this->colorConverter->rgbToOklch($targetRgb)
            : $this->colorConverter->rgbToLab($targetRgb, $this->getWhitePoint());

        // Calculate distance for each color, skipping colors whose color-space
        // values cannot be resolved (e.g. legacy rows without hex or lab data)
        $colorsWithDistance = $colors->map(function (Color $color) use ($targetColorSpace, $algorithm): ?array {
            if ($algorithm !== self::ALGORITHM_OKLCH) {
                /** @var array{l: float, a: float, b: float} $targetLab */
                $targetLab = $targetColorSpace;
                /** @var array{l: float, a: float, b: float}|null $colorLab */
                $colorLab = $color->lab;
                if ($colorLab === null) {
                    return null;
                }
                $distance = $algorithm === self::ALGORITHM_CIEDE2000
                    ? $this->deltaE2000($targetLab, $colorLab)
                    : $this->calculateLabDistance($targetLab, $colorLab);
            } else {
                /** @var array{l: float, c: float, h: float} $targetOklch */
                $targetOklch = $targetColorSpace;
                /** @var array{l: float, c: float, h: float}|null $colorOklch */
                $colorOklch = $color->oklch;
                if ($colorOklch === null) {
                    return null;
                }
                $distance = $this->calculateOklchDistance($targetOklch, $colorOklch);
            }

            return [
                'color'    => $color,
                'distance' => $distance,
            ];
        })->filter();

        // Sort by distance (ascending) and take the top N
        $sorted = $colorsWithDistance
            ->sortBy('distance')
            ->take($limit);

        // Return Color models with distance attribute attached. Clone so the
        // transient distance attribute never dirties the managed (and possibly
        // cached) model instances.
        return $sorted->map(function (array $item): Color {
            $color = clone $item['color'];
            $color->setAttribute('distance', round($item['distance'], 4));

            return $color;
        })->values();
    }

    /**
     * Calculate Delta E (CIE76) distance between two LAB colors.
     *
     * Formula: sqrt((L2-L1)^2 + (a2-a1)^2 + (b2-b1)^2)
     *
     * @param  array{l: float, a: float, b: float}  $lab1
     * @param  array{l: float, a: float, b: float}  $lab2
     */
    private function calculateLabDistance(array $lab1, array $lab2): float
    {
        $deltaL = $lab2['l'] - $lab1['l'];
        $deltaA = $lab2['a'] - $lab1['a'];
        $deltaB = $lab2['b'] - $lab1['b'];

        return sqrt($deltaL * $deltaL + $deltaA * $deltaA + $deltaB * $deltaB);
    }

    /**
     * Calculate the CIEDE2000 (Delta E 2000) color difference between two LAB colors.
     *
     * CIEDE2000 corrects CIE76's known weaknesses (over-weighting saturated
     * colors, poor blue-region accuracy) with lightness, chroma and hue
     * weighting functions plus a hue-rotation term. It is the color-difference
     * standard used in print and textile matching. Parametric factors kL, kC
     * and kH are 1 (reference conditions).
     *
     * @see https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/
     *
     * @param  array{l: float, a: float, b: float}  $lab1
     * @param  array{l: float, a: float, b: float}  $lab2
     */
    public function deltaE2000(array $lab1, array $lab2): float
    {
        $pow25To7 = 25 ** 7;

        // Adjust a* so neutral colors are handled correctly (G factor)
        $cBar = (hypot($lab1['a'], $lab1['b']) + hypot($lab2['a'], $lab2['b'])) / 2;
        $g = 0.5 * (1 - sqrt($cBar ** 7 / ($cBar ** 7 + $pow25To7)));

        $a1 = (1 + $g) * $lab1['a'];
        $a2 = (1 + $g) * $lab2['a'];

        $c1 = hypot($a1, $lab1['b']);
        $c2 = hypot($a2, $lab2['b']);

        $h1 = $this->hueAngle($a1, $lab1['b']);
        $h2 = $this->hueAngle($a2, $lab2['b']);

        // Differences in lightness, chroma and hue
        $deltaL = $lab2['l'] - $lab1['l'];
        $deltaC = $c2 - $c1;

        $deltaHue = 0.0;
        if ($c1 * $c2 !== 0.0) {
            $deltaHue = $h2 - $h1;
            if ($deltaHue > 180) {
                $deltaHue -= 360;
            } elseif ($deltaHue < -180) {
                $deltaHue += 360;
            }
        }
        $deltaH = 2 * sqrt($c1 * $c2) * sin(deg2rad($deltaHue / 2));

        // Means
        $lBar = ($lab1['l'] + $lab2['l']) / 2;
        $cBarPrime = ($c1 + $c2) / 2;

        $hBar = $h1 + $h2;
        if ($c1 * $c2 !== 0.0) {
            if (abs($h1 - $h2) <= 180) {
                $hBar /= 2;
            } elseif ($hBar < 360) {
                $hBar = ($hBar + 360) / 2;
            } else {
                $hBar = ($hBar - 360) / 2;
            }
        }

        // Weighting functions
        $t = 1
            - 0.17 * cos(deg2rad($hBar - 30))
            + 0.24 * cos(deg2rad(2 * $hBar))
            + 0.32 * cos(deg2rad(3 * $hBar + 6))
            - 0.20 * cos(deg2rad(4 * $hBar - 63));

        $lBarMinus50Squared = ($lBar - 50) ** 2;
        $sL = 1 + (0.015 * $lBarMinus50Squared) / sqrt(20 + $lBarMinus50Squared);
        $sC = 1 + 0.045 * $cBarPrime;
        $sH = 1 + 0.015 * $cBarPrime * $t;

        // Hue rotation term (corrects the blue region)
        $deltaTheta = 30 * exp(-((($hBar - 275) / 25) ** 2));
        $rC = 2 * sqrt($cBarPrime ** 7 / ($cBarPrime ** 7 + $pow25To7));
        $rT = -sin(deg2rad(2 * $deltaTheta)) * $rC;

        $lTerm = $deltaL / $sL;
        $cTerm = $deltaC / $sC;
        $hTerm = $deltaH / $sH;

        return sqrt($lTerm ** 2 + $cTerm ** 2 + $hTerm ** 2 + $rT * $cTerm * $hTerm);
    }

    /**
     * Hue angle in degrees (0-360) for the given a*, b* components.
     */
    private function hueAngle(float $a, float $b): float
    {
        if ($a === 0.0 && $b === 0.0) {
            return 0.0;
        }

        $hue = rad2deg(atan2($b, $a));

        return $hue < 0 ? $hue + 360 : $hue;
    }

    /**
     * Calculate distance between two OKLCH colors.
     *
     * OKLCH is the cylindrical form of OKLab, which is designed so that
     * Euclidean distance is perceptually uniform. Converting (C, H) back to
     * Cartesian (a, b) and measuring straight-line distance therefore weights
     * lightness, chroma, and hue correctly and handles hue wraparound
     * naturally (359deg and 1deg map to nearby points).
     *
     * @param  array{l: float, c: float, h: float}  $oklch1
     * @param  array{l: float, c: float, h: float}  $oklch2
     */
    private function calculateOklchDistance(array $oklch1, array $oklch2): float
    {
        $deltaL = $oklch2['l'] - $oklch1['l'];

        $h1 = deg2rad($oklch1['h']);
        $h2 = deg2rad($oklch2['h']);

        $deltaA = $oklch2['c'] * cos($h2) - $oklch1['c'] * cos($h1);
        $deltaB = $oklch2['c'] * sin($h2) - $oklch1['c'] * sin($h1);

        return sqrt(
            $deltaL * $deltaL +
            $deltaA * $deltaA +
            $deltaB * $deltaB
        );
    }

    /**
     * Validate that the algorithm parameter is supported.
     *
     * @throws InvalidArgumentException If algorithm is not valid
     */
    private function validateAlgorithm(string $algorithm): void
    {
        if (! in_array($algorithm, self::VALID_ALGORITHMS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid algorithm '%s'. Valid options: %s",
                    $algorithm,
                    implode(', ', self::VALID_ALGORITHMS)
                )
            );
        }
    }

    /**
     * Get the configured white point for LAB calculations.
     */
    private function getWhitePoint(): string
    {
        $whitePoint = config('laratone.white_point', 'D65');

        return is_string($whitePoint) ? $whitePoint : 'D65';
    }

    /**
     * Get available distance algorithms.
     *
     * @return array<string>
     */
    public static function availableAlgorithms(): array
    {
        return self::VALID_ALGORITHMS;
    }
}

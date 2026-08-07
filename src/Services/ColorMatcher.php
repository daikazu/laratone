<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Services;

use Daikazu\Laratone\Models\Color;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Service for finding closest matching colors from a color book.
 *
 * Supports LAB (CIE76 Delta E) and OKLCH distance algorithms for
 * perceptually accurate color matching.
 */
final readonly class ColorMatcher
{
    public const string ALGORITHM_LAB = 'lab';

    public const string ALGORITHM_OKLCH = 'oklch';

    private const array VALID_ALGORITHMS = [self::ALGORITHM_LAB, self::ALGORITHM_OKLCH];

    public function __construct(
        private ColorConverter $colorConverter
    ) {}

    /**
     * Find the closest matching colors from a collection.
     *
     * @param  string  $targetHex  The target color as a 6-character hex code (with or without #)
     * @param  Collection<int, Color>|\Illuminate\Database\Eloquent\Collection<int, Color>  $colors  The collection of colors to search
     * @param  int  $limit  Maximum number of matches to return (default: 1)
     * @param  string  $algorithm  Distance algorithm: 'lab' or 'oklch' (default: 'lab')
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
        $targetColorSpace = $algorithm === self::ALGORITHM_LAB
            ? $this->colorConverter->rgbToLab($targetRgb, $this->getWhitePoint())
            : $this->colorConverter->rgbToOklch($targetRgb);

        // Calculate distance for each color
        $colorsWithDistance = $colors->map(function (Color $color) use ($targetColorSpace, $algorithm): array {
            if ($algorithm === self::ALGORITHM_LAB) {
                /** @var array{l: float, a: float, b: float} $targetLab */
                $targetLab = $targetColorSpace;
                /** @var array{l: float, a: float, b: float} $colorLab */
                $colorLab = $color->lab;
                $distance = $this->calculateLabDistance($targetLab, $colorLab);
            } else {
                /** @var array{l: float, c: float, h: float} $targetOklch */
                $targetOklch = $targetColorSpace;
                /** @var array{l: float, c: float, h: float} $colorOklch */
                $colorOklch = $color->oklch;
                $distance = $this->calculateOklchDistance($targetOklch, $colorOklch);
            }

            return [
                'color'    => $color,
                'distance' => $distance,
            ];
        });

        // Sort by distance (ascending) and take the top N
        $sorted = $colorsWithDistance
            ->sortBy('distance')
            ->take($limit);

        // Return Color models with distance attribute attached
        return $sorted->map(function (array $item): Color {
            $color = $item['color'];
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
     * Calculate distance between two OKLCH colors.
     *
     * Uses cylindrical distance with proper hue angle handling:
     * sqrt((L2-L1)^2 + (C2-C1)^2 + hueDistance^2)
     *
     * @param  array{l: float, c: float, h: float}  $oklch1
     * @param  array{l: float, c: float, h: float}  $oklch2
     */
    private function calculateOklchDistance(array $oklch1, array $oklch2): float
    {
        $deltaL = $oklch2['l'] - $oklch1['l'];
        $deltaC = $oklch2['c'] - $oklch1['c'];

        // Handle hue angle wraparound (shortest path on the color wheel)
        $deltaH = $this->calculateHueDifference($oklch1['h'], $oklch2['h']);

        // Scale lightness difference to be comparable with chroma and hue
        // L is 0-1 in OKLCH, multiply by a factor to balance with C and H
        $scaledDeltaL = $deltaL * 0.4;

        return sqrt(
            $scaledDeltaL * $scaledDeltaL +
            $deltaC * $deltaC +
            $deltaH * $deltaH
        );
    }

    /**
     * Calculate the shortest angular difference between two hue values.
     *
     * Handles wraparound where 359deg and 1deg should be close together.
     * Returns a value scaled appropriately for use in distance calculations.
     *
     * @param  float  $h1  Hue in degrees (0-360)
     * @param  float  $h2  Hue in degrees (0-360)
     * @return float Scaled hue difference suitable for distance calculation
     */
    private function calculateHueDifference(float $h1, float $h2): float
    {
        // Calculate the angular difference
        $diff = abs($h1 - $h2);

        // Take the shortest path around the color wheel
        if ($diff > 180) {
            $diff = 360 - $diff;
        }

        // Convert to radians and scale appropriately
        // Divide by 360 and multiply by 2*pi, then scale by a factor
        // to balance with L and C in the distance formula
        return ($diff / 360) * 0.15;
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

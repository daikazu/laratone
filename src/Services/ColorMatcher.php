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

        // Calculate distance for each color, skipping colors whose color-space
        // values cannot be resolved (e.g. legacy rows without hex or lab data)
        $colorsWithDistance = $colors->map(function (Color $color) use ($targetColorSpace, $algorithm): ?array {
            if ($algorithm === self::ALGORITHM_LAB) {
                /** @var array{l: float, a: float, b: float} $targetLab */
                $targetLab = $targetColorSpace;
                /** @var array{l: float, a: float, b: float}|null $colorLab */
                $colorLab = $color->lab;
                if ($colorLab === null) {
                    return null;
                }
                $distance = $this->calculateLabDistance($targetLab, $colorLab);
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

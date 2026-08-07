<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Models;

use Carbon\Carbon;
use Daikazu\Laratone\Casts\ColorValueCast;
use Daikazu\Laratone\Enums\ColorType;
use Daikazu\Laratone\Services\ColorConverter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Color model with automatic color space calculations.
 *
 * When rgb, cmyk, or lab attributes are null, they are automatically calculated
 * from the hex value on access. Stored values always take precedence.
 *
 * Note: This model uses app(ColorConverter::class) for color calculations.
 * This creates a service container dependency, which is acceptable here because:
 * - Calculations only occur when accessing null attributes
 * - The ColorConverter is a stateless service with no side effects
 * - Dependency injection isn't practical for Eloquent attribute accessors
 *
 * @property int $id
 * @property int $color_book_id
 * @property string $name
 * @property string $hex
 * @property array{l: float, a: float, b: float}|null $lab
 * @property array{r: int, g: int, b: int}|null $rgb
 * @property array{c: int, m: int, y: int, k: int}|null $cmyk
 * @property array{l: float, c: float, h: float}|null $oklch
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Color extends Model
{
    use HasFactory;

    protected $table = 'colors';

    protected $guarded = ['id'];

    protected $hidden = ['id', 'color_book_id', 'created_at', 'updated_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $prefix = config('laratone.table_prefix');
        $this->table = (is_string($prefix) ? $prefix : '') . $this->table;
    }

    /**
     * Bootstrap the model and register event listeners.
     */
    protected static function booted(): void
    {
        static::creating(function (Color $color): void {
            $color->fillCalculatedValuesIfEnabled();
        });

        static::saving(function (Color $color): void {
            // 'distance' is a transient attribute attached by ColorMatcher
            // results; it has no database column and must never be persisted
            $color->offsetUnset('distance');
        });

        static::updating(function (Color $color): void {
            // When hex changes, stored color space values derived from the old
            // hex are stale; clear any the caller did not explicitly provide so
            // they are recalculated (persisted when pre_calculate_colors is
            // enabled, lazily on access otherwise)
            if ($color->isDirty('hex')) {
                foreach (['rgb', 'cmyk', 'lab', 'oklch'] as $attribute) {
                    if (! $color->isDirty($attribute)) {
                        $color->setAttribute($attribute, null);
                    }
                }

                $color->fillCalculatedValuesIfEnabled();
            }
        });
    }

    /**
     * Convert the model's attributes to an array.
     *
     * Mirrors getAttribute's auto-calculation so serialized output (toArray,
     * JSON responses) matches direct attribute access instead of exposing
     * null for color values that can be calculated from hex.
     *
     * @return array<string, mixed>
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach (['rgb', 'cmyk', 'lab', 'oklch'] as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] === null) {
                $attributes[$key] = $this->getAttribute($key);
            }
        }

        return $attributes;
    }

    /**
     * Fill in calculated color values if pre_calculate_colors is enabled and values are null.
     */
    protected function fillCalculatedValuesIfEnabled(): void
    {
        $preCalculate = config('laratone.pre_calculate_colors', false);

        if (! $preCalculate) {
            return;
        }

        $hex = $this->attributes['hex'] ?? null;

        if (! is_string($hex) || $hex === '') {
            return;
        }

        $calculated = self::calculateAllFromHex($hex);

        // Only fill values that are not already set (null or empty)
        $rgb = $this->attributes['rgb'] ?? null;
        if ($rgb === null || $rgb === '') {
            $this->attributes['rgb'] = implode(',', $calculated['rgb']);
        }

        $cmyk = $this->attributes['cmyk'] ?? null;
        if ($cmyk === null || $cmyk === '') {
            $this->attributes['cmyk'] = implode(',', $calculated['cmyk']);
        }

        $lab = $this->attributes['lab'] ?? null;
        if ($lab === null || $lab === '') {
            $this->attributes['lab'] = implode(',', $calculated['lab']);
        }

        $oklch = $this->attributes['oklch'] ?? null;
        if ($oklch === null || $oklch === '') {
            $this->attributes['oklch'] = implode(',', $calculated['oklch']);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lab'   => ColorValueCast::forType(ColorType::LAB),
            'rgb'   => ColorValueCast::forType(ColorType::RGB),
            'cmyk'  => ColorValueCast::forType(ColorType::CMYK),
            'oklch' => ColorValueCast::forType(ColorType::OKLCH),
        ];
    }

    /**
     * Get an attribute from the model, auto-calculating color values from hex when null.
     *
     * When accessing rgb, cmyk, or lab attributes that are null but hex is present,
     * the values are automatically calculated from the hex value.
     */
    public function getAttribute(mixed $key): mixed
    {
        $value = parent::getAttribute($key);

        // Return stored value if it exists
        if ($value !== null) {
            return $value;
        }

        // Use getAttributes() directly to avoid infinite recursion.
        // Accessing $this->hex would call getAttribute() again.
        $hex = $this->getAttributes()['hex'] ?? null;

        // Can't calculate without hex
        if (! is_string($hex) || $hex === '') {
            return $value;
        }

        // Auto-calculate from hex for color space attributes
        return match ($key) {
            'rgb'   => $this->calculateRgbFromHex($hex),
            'cmyk'  => $this->calculateCmykFromHex($hex),
            'lab'   => $this->calculateLabFromHex($hex),
            'oklch' => $this->calculateOklchFromHex($hex),
            default => $value,
        };
    }

    /**
     * Calculate RGB values from hex.
     *
     * @return array{r: int, g: int, b: int}
     */
    private function calculateRgbFromHex(string $hex): array
    {
        return app(ColorConverter::class)->hexToRgb($hex);
    }

    /**
     * Calculate CMYK values from hex.
     *
     * @return array{c: int, m: int, y: int, k: int}
     */
    private function calculateCmykFromHex(string $hex): array
    {
        $rgb = $this->calculateRgbFromHex($hex);

        return app(ColorConverter::class)->rgbToCmyk($rgb);
    }

    /**
     * Calculate LAB values from hex using configured white point.
     *
     * @return array{l: float, a: float, b: float}
     */
    private function calculateLabFromHex(string $hex): array
    {
        $rgb = $this->calculateRgbFromHex($hex);
        $whitePoint = config('laratone.white_point', 'D65');

        return app(ColorConverter::class)->rgbToLab($rgb, is_string($whitePoint) ? $whitePoint : 'D65');
    }

    /**
     * Calculate OKLCH values from hex.
     *
     * @return array{l: float, c: float, h: float}
     */
    private function calculateOklchFromHex(string $hex): array
    {
        $rgb = $this->calculateRgbFromHex($hex);

        return app(ColorConverter::class)->rgbToOklch($rgb);
    }

    /**
     * Calculate all color values from a hex code.
     *
     * Useful for pre-calculating values before saving to avoid lazy calculation overhead.
     *
     * @return array{rgb: array{r: int, g: int, b: int}, cmyk: array{c: int, m: int, y: int, k: int}, lab: array{l: float, a: float, b: float}, oklch: array{l: float, c: float, h: float}}
     */
    public static function calculateAllFromHex(string $hex, ?string $whitePoint = null): array
    {
        $converter = app(ColorConverter::class);
        $whitePoint ??= config('laratone.white_point', 'D65');
        $whitePoint = is_string($whitePoint) ? $whitePoint : 'D65';

        $rgb = $converter->hexToRgb($hex);

        return [
            'rgb'   => $rgb,
            'cmyk'  => $converter->rgbToCmyk($rgb),
            'lab'   => $converter->rgbToLab($rgb, $whitePoint),
            'oklch' => $converter->rgbToOklch($rgb),
        ];
    }

    /**
     * Get the color book that owns the color.
     *
     * @return BelongsTo<ColorBook, $this>
     */
    public function colorBook(): BelongsTo
    {
        return $this->belongsTo(ColorBook::class, 'color_book_id', 'id');
    }
}

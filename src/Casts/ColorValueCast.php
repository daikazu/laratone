<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Casts;

use Daikazu\Laratone\Enums\ColorType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<array<string, int|float>|null, string|null>
 */
final readonly class ColorValueCast implements CastsAttributes
{
    /**
     * @var array<string>
     */
    private array $keys;

    private string $valueType;

    /**
     * Laravel passes cast arguments as separate parameters (commas are parameter separators).
     * For RGB: ColorValueCast:r,g,b,int -> receives ('r', 'g', 'b', 'int')
     * For CMYK: ColorValueCast:c,m,y,k,int -> receives ('c', 'm', 'y', 'k', 'int')
     * For LAB: ColorValueCast:l,a,b,float -> receives ('l', 'a', 'b', 'float')
     */
    public function __construct(string ...$args)
    {
        // Last argument is the type (int or float), rest are keys
        $this->valueType = array_pop($args) ?? 'float';
        $this->keys = $args;
    }

    public static function forType(ColorType $colorType): string
    {
        $keys = implode(',', $colorType->components());
        $type = $colorType->valueType();

        return self::class . ":{$keys},{$type}";
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|float>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $values = explode(',', (string) $value);

        if (count($values) !== count($this->keys)) {
            return null;
        }

        $values = match ($this->valueType) {
            'int'   => array_map(intval(...), $values),
            default => array_map(floatval(...), $values),
        };

        return array_combine($this->keys, $values);
    }

    /**
     * @param  array<string, int|float>|string|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Handle both array (from casted model) and string (from factory/raw input)
        if (is_array($value)) {
            return implode(',', $this->normalizeComponents($value, $key));
        }

        return (string) $value;
    }

    /**
     * Validate array components and order them canonically.
     *
     * Associative arrays are reordered by the canonical component keys so
     * insertion order cannot silently transpose channels (e.g. supplying
     * ['b' => .., 'r' => .., 'g' => ..] for an RGB value). List arrays are
     * assumed to be in canonical order and validated for length.
     *
     * @param  array<int|string, int|float|string>  $value
     * @return array<int|float|string>
     */
    private function normalizeComponents(array $value, string $attribute): array
    {
        if (array_is_list($value)) {
            if (count($value) !== count($this->keys)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid %s value: expected %d components, got %d.',
                    $attribute,
                    count($this->keys),
                    count($value)
                ));
            }

            return $value;
        }

        $missing = array_diff($this->keys, array_keys($value));
        $unknown = array_diff(array_keys($value), $this->keys);

        if ($missing !== [] || $unknown !== []) {
            throw new InvalidArgumentException(sprintf(
                'Invalid %s value: expected keys [%s], got [%s].',
                $attribute,
                implode(',', $this->keys),
                implode(',', array_map(strval(...), array_keys($value)))
            ));
        }

        return array_map(fn (string $key): int|float|string => $value[$key], $this->keys);
    }
}

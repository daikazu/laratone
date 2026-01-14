<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Casts;

use Daikazu\Laratone\Enums\ColorType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

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
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Handle both array (from casted model) and string (from factory/raw input)
        if (is_array($value)) {
            return implode(',', $value);
        }

        return (string) $value;
    }
}

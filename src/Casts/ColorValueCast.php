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

    /**
     * Laravel passes cast arguments as strings, so we accept string and parse it.
     */
    public function __construct(
        string $keys,
        private string $type = 'float',
    ) {
        $this->keys = explode(',', $keys);
    }

    public static function forType(ColorType $colorType): string
    {
        $keys = implode(',', $colorType->components());
        $type = $colorType->valueType();

        return self::class . ":{$keys}:{$type}";
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

        $values = match ($this->type) {
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
        // @phpstan-ignore function.impossibleType (runtime can receive both types)
        if (is_array($value)) {
            return implode(',', $value);
        }

        return (string) $value;
    }
}

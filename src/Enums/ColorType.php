<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Enums;

enum ColorType: string
{
    case LAB = 'lab';
    case RGB = 'rgb';
    case CMYK = 'cmyk';
    case HEX = 'hex';

    /**
     * @return array<string>
     */
    public function components(): array
    {
        return match ($this) {
            self::LAB  => ['l', 'a', 'b'],
            self::RGB  => ['r', 'g', 'b'],
            self::CMYK => ['c', 'm', 'y', 'k'],
            self::HEX  => [],
        };
    }

    public function valueType(): string
    {
        return match ($this) {
            self::LAB => 'float',
            self::RGB, self::CMYK => 'int',
            self::HEX => 'string',
        };
    }
}

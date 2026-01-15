<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Data;

use stdClass;

final readonly class ColorBookData
{
    /**
     * @param  array<ColorData>  $colors
     */
    public function __construct(
        public string $name,
        public array $colors,
    ) {}

    public static function fromJson(stdClass $json): self
    {
        $colors = array_map(
            ColorData::fromJson(...),
            $json->data ?? []
        );

        return new self(
            name: $json->name ?? '',
            colors: $colors,
        );
    }
}

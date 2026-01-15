<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Data;

use stdClass;

final readonly class ColorData
{
    public function __construct(
        public string $name,
        public string $hex,
        public ?string $lab = null,
        public ?string $rgb = null,
        public ?string $cmyk = null,
    ) {}

    public static function fromJson(stdClass $json): self
    {
        return new self(
            name: trim($json->name ?? ''),
            hex: $json->hex ?? '',
            lab: $json->lab ?? null,
            rgb: $json->rgb ?? null,
            cmyk: $json->cmyk ?? null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'lab'  => $this->lab,
            'hex'  => $this->hex,
            'rgb'  => $this->rgb,
            'cmyk' => $this->cmyk,
        ];
    }
}

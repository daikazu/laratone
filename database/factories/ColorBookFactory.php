<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Database\Factories;

use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ColorBook>
 */
final class ColorBookFactory extends Factory
{
    protected $model = ColorBook::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}

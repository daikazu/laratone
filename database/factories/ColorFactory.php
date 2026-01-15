<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Database\Factories;

use Daikazu\Laratone\Faker\ColorFakerProvider;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Color>
 */
final class ColorFactory extends Factory
{
    protected $model = Color::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $this->faker->addProvider(new ColorFakerProvider($this->faker));

        return [
            'color_book_id' => ColorBook::factory(),
            'name'          => $this->faker->colorName(),
            'lab'           => $this->faker->labColor(),
            'hex'           => $this->faker->hexColor(),
            'rgb'           => $this->faker->rgbColor(),
            'cmyk'          => $this->faker->cmykColor(),
            'oklch'         => $this->faker->oklchColor(),
        ];
    }
}

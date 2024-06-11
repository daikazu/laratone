<?php

namespace Daikazu\Laratone\Database\Factories;

use Daikazu\Laratone\Faker\ColorFakerProvider;
use Daikazu\Laratone\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

class ColorFactory extends Factory
{
    protected $model = Color::class;

    public function definition(): array
    {

        // Add the new provider to the Faker generator
        $this->faker->addProvider(new ColorFakerProvider($this->faker));

        return [
            'color_book_id' => ColorBookFactory::new()->create()->id,
            'name'          => $this->faker->colorName(),
            'lab'           => $this->faker->labColor(),
            'hex'           => $this->faker->hexColor(),
            'rgb'           => $this->faker->rgbColor(),
            'cmyk'          => $this->faker->cmykColor(),
        ];
    }
}

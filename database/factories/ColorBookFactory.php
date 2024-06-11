<?php

namespace Daikazu\Laratone\Database\Factories;

use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ColorBookFactory extends Factory
{
    protected $model = ColorBook::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}

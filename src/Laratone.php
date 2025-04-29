<?php

namespace Daikazu\Laratone;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Laratone
{
    /**
     * Get all color books with their associated colors.
     *
     * @return Collection<int, ColorBook>
     */
    public function colorBooks(): Collection
    {
        return Cache::remember('laratone.color_books', config('laratone.cache_time'), fn () => ColorBook::with('colors')->get());
    }

    /**
     * Get a color book by its slug.
     *
     * @param  string  $colorBookSlug  The slug of the color book to retrieve
     * @return ColorBook|null The color book if found, null otherwise
     */
    public function colorBookBySlug(string $colorBookSlug): ?ColorBook
    {
        return Cache::remember(
            key: "laratone.color_book.{$colorBookSlug}",
            ttl: config('laratone.cache_time'),
            callback: fn () => ColorBook::slug($colorBookSlug)->first()
        );
    }

    /**
     * Create a new color book.
     *
     * @param  string  $name  The name of the color book
     * @param  string|null  $slug  Optional slug. If not provided, will be generated from the name
     * @return ColorBook The newly created color book
     */
    public function createColorBook(string $name, ?string $slug = null): ColorBook
    {
        $slug ??= Str::slug($name);

        $colorBook = ColorBook::create([
            'name' => $name,
            'slug' => $slug,
        ]);

        $this->clearCache();

        return $colorBook;
    }

    /**
     * Clear all cached color book data.
     */
    public function clearCache(): void
    {
        Cache::forget('laratone.color_books');
        Cache::tags(['laratone'])->flush();
    }

    /**
     * Add a color to a color book.
     *
     * @param  ColorBook  $colorBook  The color book to add the color to
     * @param  array  $colorData  The color data to add
     * @return Color The newly created color
     */
    public function addColorToBook(ColorBook $colorBook, array $colorData): Color
    {
        $color = $colorBook->colors()->create($colorData);
        $this->clearCache();

        return $color;
    }

    /**
     * Add multiple colors to a color book.
     *
     * @param  ColorBook  $colorBook  The color book to add the colors to
     * @param  array  $colorsData  Array of color data arrays
     * @return Collection<int, Color> The newly created colors
     */
    public function addColorsToBook(ColorBook $colorBook, array $colorsData): Collection
    {
        $colors = $colorBook->colors()->createMany($colorsData);
        $this->clearCache();

        return $colors;
    }

    /**
     * Update a color in a color book.
     *
     * @param  Color  $color  The color to update
     * @param  array  $colorData  The new color data
     * @return bool Whether the update was successful
     */
    public function updateColor(Color $color, array $colorData): bool
    {
        $result = $color->update($colorData);
        $this->clearCache();

        return $result;
    }

    /**
     * Delete a color from a color book.
     *
     * @param  Color  $color  The color to delete
     * @return bool Whether the deletion was successful
     */
    public function deleteColor(Color $color): bool
    {
        $result = $color->delete();
        $this->clearCache();

        return $result;
    }

    /**
     * Get all colors from a color book.
     *
     * @param  ColorBook  $colorBook  The color book to get colors from
     * @return Collection<int, Color> The colors in the color book
     */
    public function getColorsFromBook(ColorBook $colorBook): Collection
    {
        return Cache::remember(
            key: "laratone.color_book.{$colorBook->slug}.colors",
            ttl: config('laratone.cache_time'),
            callback: fn () => $colorBook->colors()->get()
        );
    }
}

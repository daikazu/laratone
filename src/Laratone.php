<?php

namespace Daikazu\Laratone;

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
        return Cache::remember('laratone.color_books', 3600, function () {
            return ColorBook::with('colors')->get();
        });
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
            "laratone.color_book.{$colorBookSlug}",
            3600,
            function () use ($colorBookSlug) {
                return ColorBook::slug($colorBookSlug)->first();
            }
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
        $slug = $slug ?? Str::slug($name);

        return ColorBook::create([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    /**
     * Clear all cached color book data.
     */
    public function clearCache(): void
    {
        Cache::forget('laratone.color_books');
        Cache::tags(['laratone'])->flush();
    }
}

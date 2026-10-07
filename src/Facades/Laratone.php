<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Daikazu\Laratone\Models\ColorBook> colorBooks()
 * @method static \Daikazu\Laratone\Models\ColorBook|null colorBookBySlug(string $colorBookSlug)
 * @method static \Daikazu\Laratone\Models\ColorBook createColorBook(string $name, string|null $slug = null)
 * @method static \Daikazu\Laratone\Models\Color addColorToBook(\Daikazu\Laratone\Models\ColorBook $colorBook, mixed[] $colorData)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Daikazu\Laratone\Models\Color> addColorsToBook(\Daikazu\Laratone\Models\ColorBook $colorBook, mixed[][] $colorsData)
 * @method static bool updateColor(\Daikazu\Laratone\Models\Color $color, mixed[] $colorData)
 * @method static bool deleteColor(\Daikazu\Laratone\Models\Color $color)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Daikazu\Laratone\Models\Color> getColorsFromBook(\Daikazu\Laratone\Models\ColorBook $colorBook)
 * @method static \Illuminate\Support\Collection<int, \Daikazu\Laratone\Models\Color> findClosestColors(\Daikazu\Laratone\Models\ColorBook $colorBook, string $targetHex, int $limit = 1, string $algorithm = 'lab')
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Daikazu\Laratone\Models\Color> searchColors(\Daikazu\Laratone\Models\ColorBook $colorBook, string $query, int $limit = 25)
 * @method static \Illuminate\Support\Collection<int, \Daikazu\Laratone\Models\Color> findClosestColorsInAllBooks(string $targetHex, int $limit = 1, string $algorithm = 'lab')
 * @method static void clearCache()
 * @method static string cacheKey(string $suffix)
 * @method static int cacheTime()
 *
 * @see \Daikazu\Laratone\Laratone
 */
final class Laratone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Daikazu\Laratone\Laratone::class;
    }
}

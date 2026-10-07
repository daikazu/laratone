---
name: laratone-development
description: Work with Laratone color books and colors in a Laravel app - creating color books, adding, updating and deleting colors, seeding color books from JSON, reading color values (hex, RGB, CMYK, LAB, OKLCH), caching, configuration, and the Laratone REST API routes and middleware. Use when the user mentions Laratone, color books, swatches, Pantone-style color libraries, or the laratone:seed command.
---

# Laratone Development

## When to use this skill

Use this skill when managing Laratone color books or colors, seeding color libraries, reading color values, configuring Laratone, or customizing its API routes. For finding similar colors or searching by name, use the `laratone-color-matching` skill.

## Always go through the facade

The `Daikazu\Laratone\Facades\Laratone` facade caches reads and clears the cache after every write. Creating or changing `Color` / `ColorBook` models directly does not clear the cache, so cached API responses and facade reads go stale until `Laratone::clearCache()` runs.

```php
use Daikazu\Laratone\Facades\Laratone;

// Read
$colorBooks = Laratone::colorBooks();                                        // all books with their colors
$colorBook  = Laratone::colorBookBySlug('color-book-plus-solid-coated');     // ColorBook or null
$colors     = Laratone::getColorsFromBook($colorBook);

// Write (each call clears the cache)
$colorBook = Laratone::createColorBook('Brand Colors');                      // slug generated: brand-colors
$colorBook = Laratone::createColorBook('Brand Colors', 'brand');            // custom slug

$color = Laratone::addColorToBook($colorBook, ['name' => 'Brand Orange', 'hex' => 'FF5500']);

$colors = Laratone::addColorsToBook($colorBook, [
    ['name' => 'Brand Red', 'hex' => 'E4002B'],
    ['name' => 'Brand Blue', 'hex' => '0085CA'],
]);

Laratone::updateColor($color, ['name' => 'Signal Orange']);
Laratone::deleteColor($color);
```

## Color values

- `name` and `hex` are required. Use a 6-character hex value without `#`, in uppercase (`FF5500`).
- `rgb`, `cmyk`, `lab` and `oklch` are optional. When they are not stored, they are calculated from hex on access.
- Stored values always take precedence. Store official values (for example a library's published LAB values) when you have them, because they are more accurate than values calculated from hex.
- Changing a color's hex clears stored values derived from the old hex, unless you pass new ones in the same update.
- Values can be passed as comma-separated strings or associative arrays, and are always read back as associative arrays:

```php
Laratone::addColorToBook($colorBook, [
    'name' => 'Solid Coated Red',
    'hex'  => 'FF0000',
    'lab'  => '53.23,80.11,67.22',                      // or ['l' => 53.23, 'a' => 80.11, 'b' => 67.22]
]);

$color->rgb;    // ['r' => 255, 'g' => 0, 'b' => 0]
$color->cmyk;   // ['c' => 0, 'm' => 100, 'y' => 100, 'k' => 0]
$color->lab;    // ['l' => 53.23, 'a' => 80.11, 'b' => 67.22]
$color->oklch;  // ['l' => 0.6279, 'c' => 0.2577, 'h' => 29.23]
$color->colorBook->name;
```

Array keys must be the canonical ones (`r,g,b`, `c,m,y,k`, `l,a,b`, `l,c,h`) with the right number of components, or an `InvalidArgumentException` is thrown.

## Seeding color books

```bash
php artisan laratone:seed                              # every bundled color book
php artisan laratone:seed ColorBookPlusSolidCoated     # one bundled book, by file name
php artisan laratone:seed --file=database/colors/brand.json   # a custom file (relative to base_path, or absolute)
```

A color book that already exists (same slug) is skipped. Colors with an invalid hex are skipped with a warning. Custom files use this format, where only `name` and `hex` are required per color:

```json
{
  "name": "Brand Colors",
  "data": [
    { "name": "Brand Orange", "hex": "FF5500" },
    { "name": "Brand Red", "hex": "E4002B", "lab": "47.41,75.29,44.4", "rgb": "228,0,43", "cmyk": "0,93,79,0" }
  ]
}
```

## Configuration

Publish with `php artisan vendor:publish --tag=laratone-config`. Key options in `config/laratone.php`:

| Option | Default | Purpose |
|--------|---------|---------|
| `table_prefix` | `laratone_` | Prefix for the `color_books` and `colors` tables |
| `cache_time` | `3600` | Cache lifetime in seconds |
| `white_point` | `D65` | Reference white for LAB calculations (`D50` suits print) |
| `pre_calculate_colors` | `false` | Store calculated values when saving instead of calculating on read |
| `default_match_algorithm` | `lab` | `lab`, `ciede2000` or `oklch` |
| `max_match_limit` | `100` | Maximum results for find-closest and search |
| `rate_limit` | `60,1` | API throttle as `maxAttempts,decayMinutes`; `null` turns it off |
| `routes.enabled` | `true` | Set to `false` to register no API routes |
| `routes.prefix` | `api/laratone` | URL prefix for every endpoint |

Migrations are published with `php artisan vendor:publish --tag=laratone-migrations`, then `php artisan migrate`.

## REST API

All routes are GET, use the `api` middleware group plus a `laratone` middleware alias, and live under `routes.prefix`:

| Route name | Path | Purpose |
|------------|------|---------|
| `laratone.colorbooks` | `colorbooks?sort=asc` | List color books |
| `laratone.colorbook` | `colorbook/{slug}?sort=&limit=&random=` | One book with its colors |
| `laratone.colorbook.search` | `colorbook/{slug}/search?q=&limit=` | Search a book by color name |
| `laratone.colorbook.find-closest` | `colorbook/{slug}/find-closest?hex=&limit=&algorithm=` | Closest colors in one book |
| `laratone.find-closest` | `find-closest?hex=&limit=&algorithm=` | Closest colors across all books |

To add authentication or logging, re-point the `laratone` alias to your own middleware in a service provider's `boot()` method. Set `rate_limit` to `null` if your middleware does its own throttling.

```php
use Illuminate\Routing\Router;

public function boot(Router $router): void
{
    $router->aliasMiddleware('laratone', \App\Http\Middleware\LaratoneApiMiddleware::class);
}
```

## Checking an installation

Run `php artisan about --only=laratone` to see the installed version and the main settings.

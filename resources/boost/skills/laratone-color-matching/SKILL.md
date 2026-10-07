---
name: laratone-color-matching
description: Find the closest matching colors to a hex value and search colors by name with Laratone - choosing between the lab (CIE76), ciede2000 (Delta E 2000) and oklch algorithms, matching within one color book or across all books, interpreting distance values, and the find-closest and search API endpoints. Use when the user wants to match, compare or look up colors, find the nearest Pantone/ink/thread color, or calculate Delta E.
---

# Laratone Color Matching

## When to use this skill

Use this skill when finding the closest colors to a target hex value, comparing two colors, choosing a matching algorithm, or searching a color book by name or code. For creating books, adding colors or configuration, use the `laratone-development` skill.

## Choosing an algorithm

| Algorithm | What it measures | Use it for |
|-----------|------------------|------------|
| `ciede2000` | ΔE2000, the industry color-difference standard | Print, ink, paint and textile libraries. The most accurate match to what people see |
| `lab` | CIE76: straight-line distance in LAB space | The default. Fast and simple, but over-weights saturated colors and is less accurate for blues |
| `oklch` | Distance in the OKLab color space | Screen and UI colors |

Prefer `ciede2000` for physical color libraries unless the user asks for another algorithm. The default comes from `config('laratone.default_match_algorithm')`, which is `lab` unless changed.

## Interpreting distances

- Lower is closer, and results are always sorted closest first.
- For `lab` and `ciede2000`, about 1 is the smallest difference most people can see, and under 2–3 is a close match.
- `oklch` distances are on a much smaller scale (around 0.01–0.02 for a close match).
- Distances from different algorithms can't be compared with each other.

## Matching within one color book

```php
use Daikazu\Laratone\Facades\Laratone;

$colorBook = Laratone::colorBookBySlug('color-book-plus-solid-coated');

$closest = Laratone::findClosestColors($colorBook, 'FF5500');      // single closest color, default algorithm

$closest = Laratone::findClosestColors(
    colorBook: $colorBook,
    targetHex: 'FF5500',
    limit: 5,
    algorithm: 'ciede2000',
);

foreach ($closest as $color) {
    echo "{$color->name} ({$color->hex}): {$color->distance}";     // distance is rounded to 4 decimals
}
```

## Matching across all color books

Use this to find which library has the nearest match. Each result has its `colorBook` relation loaded.

```php
$closest = Laratone::findClosestColorsInAllBooks('FF5500', limit: 3, algorithm: 'ciede2000');

foreach ($closest as $color) {
    echo "{$color->colorBook->name}: {$color->name} ({$color->distance})";
}
```

## Comparing two colors directly

`ColorMatcher::deltaE2000()` returns the ΔE2000 difference between two LAB colors. Use `ColorConverter` to get LAB values from hex, passing the configured white point.

```php
use Daikazu\Laratone\Services\ColorConverter;
use Daikazu\Laratone\Services\ColorMatcher;

$converter = app(ColorConverter::class);
$whitePoint = config('laratone.white_point', 'D65');

$lab1 = $converter->rgbToLab($converter->hexToRgb('FF5500'), $whitePoint);
$lab2 = $converter->rgbToLab($converter->hexToRgb('FC4C02'), $whitePoint);

$deltaE = app(ColorMatcher::class)->deltaE2000($lab1, $lab2);
```

## Searching by name or code

Search is case-insensitive and matches the text anywhere in the color name. Results are ordered by name, 25 by default.

```php
$colors = Laratone::searchColors($colorBook, '185');          // '185 C', '2185 C', '5185 C'
$colors = Laratone::searchColors($colorBook, 'orange', 10);
```

## API endpoints

All are GET requests under the configured prefix (default `/api/laratone`):

- `colorbook/{slug}/find-closest?hex=FF5500&limit=3&algorithm=ciede2000`: closest colors in one book.
- `find-closest?hex=FF5500&limit=3&algorithm=ciede2000`: closest colors across all books. Each match includes `color_book: {name, slug}`.
- `colorbook/{slug}/search?q=185&limit=10`: search a book by color name.

`hex` is required and accepts 6 characters with or without `#`, in either case. `limit` can't exceed `max_match_limit` (default 100). Invalid input returns 422, and an unknown color book returns 404.

## Notes

- Matching loads the colors being compared into memory and calculates a distance for each one. This is fast for the bundled libraries (about 40 ms across 2,900 colors), and results are cached.
- Colors whose values can't be resolved (for example, legacy rows without hex or LAB data) are skipped rather than causing an error.
- An invalid algorithm name throws an `InvalidArgumentException`. Valid names are listed by `ColorMatcher::availableAlgorithms()`.

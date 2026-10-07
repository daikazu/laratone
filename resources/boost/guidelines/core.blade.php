## Laratone

Laratone manages color libraries (color books such as Solid Coated, metallic and thread colors) in a Laravel app. It stores colors with hex, RGB, CMYK, LAB and OKLCH values, finds the closest matching colors to a hex value, searches colors by name, and serves everything over a cached REST API.

### Key facts

- Use the `Daikazu\Laratone\Facades\Laratone` facade for all reads and writes. It caches results and clears the cache on every change; writing through the models directly bypasses that.
- Models: `Daikazu\Laratone\Models\ColorBook` (has many `colors`, looked up by `slug`) and `Daikazu\Laratone\Models\Color` (belongs to `colorBook`).
- Only `name` and `hex` are required for a color. Hex is 6 characters without `#` (e.g. `FF5500`). RGB, CMYK, LAB and OKLCH are calculated from hex when not stored, and stored values always take precedence.
- Color values are read as associative arrays: `$color->rgb` is `['r' => 255, 'g' => 85, 'b' => 0]`, `$color->lab` is `['l' => ..., 'a' => ..., 'b' => ...]`.
- Matching algorithms are `lab` (CIE76, the default), `ciede2000` (ΔE2000, the print and textile standard) and `oklch`. Prefer `ciede2000` when matching against print, ink or thread libraries.
- Seed the bundled color books with `php artisan laratone:seed`, and clear the cache with `php artisan laratone:clear-cache`.
- Config lives in `config/laratone.php` (publish with `php artisan vendor:publish --tag=laratone-config`).

@verbatim
<code-snippet name="Find the closest colors to a hex value" lang="php">
use Daikazu\Laratone\Facades\Laratone;

$colorBook = Laratone::colorBookBySlug('color-book-plus-solid-coated');

$matches = Laratone::findClosestColors($colorBook, 'FF5500', limit: 3, algorithm: 'ciede2000');

foreach ($matches as $color) {
    echo "{$color->name} ({$color->hex}): {$color->distance}";
}
</code-snippet>
@endverbatim

Use the `laratone-development` skill when creating color books, adding colors, seeding, or configuring the API, and the `laratone-color-matching` skill when matching or searching colors.

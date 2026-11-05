# Changelog

All notable changes to `laratone` will be documented in this file.

## v5.2.1 - 2026-11-05

### Fixes

Nine colors in two Metallic color books had hex values corrupted to scientific notation in the source data, such as `8.94E+87`. The seeder stripped the `.` and `+`, so these colors were stored with a valid but wrong hex. Each one is now restored from its `rgb` value.

| Color book | Color | Stored before | Correct hex |
| --- | --- | --- | --- |
| Color Book Metallic Coated | 8124 C | `894E87` | `894E85` |
| Color Book Metallic Coated | 8165 C | `523E83` | `523E81` |
| Color Book Metallic Coated | 8285 C | `558E79` | `558E77` |
| Color Book Metallic Coated | 8544 C | `643E56` | `643E54` |
| Color Book Metallic Coated | 8741 C | `346E63` | `346E61` |
| Color Book Metallic Coated | 8762 C | `355E70` | `355E68` |
| Color Book Metallic Coated | 8960 C | `966E34` | `966E32` |
| Color Book Plus Metallic Coated | 10264 C | `647E92` | `647E90` |
| Color Book Plus Metallic Coated | 10385 C | `675E62` | `675E60` |

The test for shipped color books now checks the raw hex value, so this kind of corruption fails the test suite.

### Upgrading

If you already seeded either book, updating the package doesn't change your data, because `laratone:seed` skips books that already exist. To get the fix, delete the affected books and seed them again:

```bash
php artisan tinker --execute="Daikazu\Laratone\Models\ColorBook::whereIn('slug', ['color-book-metallic-coated', 'color-book-plus-metallic-coated'])->delete();"
php artisan laratone:seed ColorBookMetallicCoated
php artisan laratone:seed ColorBookPlusMetallicCoated
php artisan laratone:clear-cache

```
This gives the colors in those books new IDs, so update anything in your app that stores them.

## v5.2.0 - 2026-10-07

### Highlights

- **CIEDE2000 (ΔE2000) color matching:** new `ciede2000` algorithm, the color-difference standard used in print and textile matching. Verified against all 34 Sharma et al. reference pairs
- **Search colors by name or code:** find `185 C` without knowing its hex
- **Find the closest color across all color books:** see which library has the nearest match
- **PHP 8.5 support:** CI now covers PHP 8.3/8.4/8.5 × Laravel 12/13 on Linux and Windows
- **One less dependency:** `spatie/laravel-package-tools` is no longer required

### New features

- `ciede2000` matching algorithm for find-closest (`?algorithm=ciede2000`, `Laratone::findClosestColors()`, or `default_match_algorithm`). `ColorMatcher::deltaE2000()` is public for comparing two LAB colors directly
- `Laratone::searchColors($colorBook, '185')` and `GET /api/laratone/colorbook/{slug}/search?q=`: case-insensitive partial name search, limit capped by `max_match_limit`
- `Laratone::findClosestColorsInAllBooks()` and `GET /api/laratone/find-closest?hex=`: each match includes the color book it came from
- New `routes.enabled` / `routes.prefix` config options to turn off the API routes or change their URL prefix. Route names are unchanged
- `php artisan about` now shows a Laratone section (version, table prefix, white point, match algorithm, pre-calculation, rate limit)
- Laravel Boost guideline plus `laratone-development` and `laratone-color-matching` skills, picked up automatically by `boost:install` / `boost:update`

### Improvements

- `Laratone`, `ColorConverter` and `ColorMatcher` are registered as singletons
- The `Laratone` facade has `@method` hints for IDE autocompletion
- Artisan commands use Laravel's console components for output
- Allows Pest v5 in dev dependencies
- README: new sections for search, cross-book matching, route config and Boost. The configuration example now lists every option, and example API responses come from real data

### Upgrading

No changes are required. Every default matches v5.1, so matching results, routes and API responses stay the same unless you opt in to the new options.

To use the new config options, add them to your published `config/laratone.php` (or re-publish it with `php artisan vendor:publish --tag=laratone-config --force`):

```php
'default_match_algorithm' => 'ciede2000', // optional: switch matching to ΔE2000

'routes' => [
    'enabled' => true,
    'prefix'  => 'api/laratone',
],


```
### What's Changed

* v5.2: ΔE2000 matching, color search, cross-book matching, PHP 8.5, Pest 5 by @daikazu in https://github.com/daikazu/laratone/pull/11

**Full Changelog**: https://github.com/daikazu/laratone/compare/v5.1.0...v5.2.0

## v5.1.0 - 2026-08-07

### Highlights

- **Laravel 13 support** — the package now supports Laravel 12.x and 13.x
- **PHP 8.3 floor** — the PHP requirement was lowered from ^8.4 to ^8.3 (matching Laravel 13); CI now covers PHP 8.3/8.4 × Laravel 12/13 on Linux and Windows
- **15 correctness fixes** from a whole-package review, each with a regression test

### Bug fixes

- `laratone:seed` no longer fails on the shipped metallic colorbooks (7 Excel-corrupted hex values repaired); invalid colors are skipped with a warning instead of aborting the run, stray non-JSON files are ignored, and Windows absolute `--file` paths work
- `clearCache()` / `laratone:clear-cache` now invalidate **all** cached data, including HTTP response caches and entries for deleted color books (cache keys are now versioned)
- OKLCH color matching uses proper OKLab distance — the hue component was previously under-weighted ~40×
- `ColorSeeder` stores real LAB values (previously stored raw XYZ)
- LAB conversions apply Bradford chromatic adaptation for non-D65 white points
- `toArray()`/JSON serialization auto-calculates color values like property access does
- Updating a color's hex clears stale derived rgb/cmyk/lab/oklch values
- `ColorValueCast` orders associative arrays by canonical keys and validates component counts (out-of-order arrays previously transposed channels silently)
- The transient `distance` attribute on find-closest results no longer breaks `save()`
- find-closest skips legacy rows with unresolvable color data instead of returning HTTP 500

### Behavior changes

- **Rate limiting is on by default again** (60 req/min, as in v4). Configure or disable via the new `rate_limit` config option
- `ColorValueCast` now throws `InvalidArgumentException` for arrays with wrong keys or component counts instead of storing corrupt data

### Upgrading

If your database was created on v4.x, publish and run the new migration to add the `oklch` column:

```bash
php artisan vendor:publish --tag=laratone-migrations
php artisan migrate



```
See [UPGRADE.md](https://github.com/daikazu/laratone/blob/master/UPGRADE.md) for the full guide.

### What's Changed

* v5.0 - PHP 8.4, Laravel 12, and new features by @daikazu in https://github.com/daikazu/laratone/pull/8
* Laravel 13 support + 15 correctness fixes from whole-package review by @daikazu in https://github.com/daikazu/laratone/pull/9

**Full Changelog**: https://github.com/daikazu/laratone/compare/v5.0.0...v5.1.0

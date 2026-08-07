# Changelog

All notable changes to `laratone` will be documented in this file.

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

# Upgrade Guide

## Upgrading from v4.x to v5.x

Version 5.0 is a major release with breaking changes. This guide will help you upgrade from v4.x to v5.x.

### Requirements

**Before upgrading, ensure your environment meets the new requirements:**

| Requirement | v4.x | v5.x |
|-------------|------|------|
| PHP | 8.3+ | 8.4+ |
| Laravel | 11.x | 12.x |

> **Important:** v5.x drops support for PHP 8.3 and Laravel 11. If you need to support older versions, continue using v4.x.

### Step 1: Update Composer Dependencies

Update your `composer.json` to require v5:

```bash
composer require daikazu/laratone:^5.0
```

### Step 2: Update Relationship Access

The `color_book` relationship on the `Color` model has been renamed to `colorBook` to follow Laravel conventions.

**Before (v4.x):**
```php
$color->color_book;
$color->color_book->name;
```

**After (v5.x):**
```php
$color->colorBook;
$color->colorBook->name;
```

Search your codebase for `color_book` and update to `colorBook`:

```bash
# Find usages in your project
grep -r "color_book" app/ resources/
```

### Step 3: Update Color Value Access

Color values (LAB, RGB, CMYK) are now cast using a custom cast class. The format remains the same, but the underlying implementation has changed.

**No changes required** if you're accessing values like this:
```php
$color->rgb;  // ['r' => 255, 'g' => 0, 'b' => 0]
$color->lab;  // ['l' => 53.23, 'a' => 80.11, 'b' => 67.22]
$color->cmyk; // ['c' => 0, 'm' => 100, 'y' => 100, 'k' => 0]
```

**New in v5.x:** RGB, CMYK, and LAB values are now automatically calculated from the hex value if not stored. This means you only need to provide hex when creating colors - other values are optional.

### Step 4: Review Deprecated Method Removals

The following deprecated methods have been removed:

| Removed | Replacement |
|---------|-------------|
| `Color::getLabAttribute()` | Use `$color->lab` directly (auto-cast) |
| `Color::getRgbAttribute()` | Use `$color->rgb` directly (auto-cast) |
| `Color::getCmykAttribute()` | Use `$color->cmyk` directly (auto-cast) |

### Step 5: Update API Consumers

If you're consuming the Laratone API, be aware of the following changes:

**Slug Validation**: The `{slug}` parameter now only accepts lowercase letters, numbers, and hyphens.

```http
# Valid
GET /api/laratone/colorbook/pantone-solid-coated

# Invalid (will return 404)
GET /api/laratone/colorbook/Pantone_Solid_Coated
```

### Step 6: Clear Application Cache

After upgrading, clear your application cache:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Step 7: Update Configuration (Optional)

A new `white_point` configuration option has been added for LAB color calculations. If you've published the config, you may want to add it:

```php
// config/laratone.php
return [
    'table_prefix' => 'laratone_',
    'cache_time' => 3600,

    // NEW: Reference white point for LAB calculations
    // Options: 'D50' (print), 'D55', 'D65' (default), 'D75'
    'white_point' => 'D65',
];
```

### Breaking Changes Summary

| Change | Impact | Action Required |
|--------|--------|-----------------|
| PHP 8.4 required | High | Upgrade PHP |
| Laravel 12 required | High | Upgrade Laravel |
| `color_book` → `colorBook` | Medium | Update relationship access |
| Hex value now required | Medium | Ensure all colors have hex values |
| Accessor methods removed | Low | Use property access instead |
| Slug route validation | Low | Ensure slugs are lowercase |

### New Features in v5.x

- **Auto-Calculation of Color Values**: RGB, CMYK, and LAB values are automatically calculated from hex when not provided
- **Configurable White Point**: New `white_point` config option for LAB color calculations (D50, D55, D65, D75)
- **ColorConverter Service**: New service class for color space conversions
- **ColorType Enum**: Type-safe enum for color types (`ColorType::LAB`, `ColorType::RGB`, etc.)
- **Custom Color Cast**: `ColorValueCast` for automatic color value parsing
- **Form Request Validation**: `ColorBookRequest` for validated API inputs
- **Data Transfer Objects**: `ColorData` and `ColorBookData` for type-safe data handling
- **Improved Caching**: Better cache driver compatibility (works with all drivers, not just tagged caches)

### Getting Help

If you encounter issues during the upgrade:

1. Check the [GitHub Issues](https://github.com/daikazu/laratone/issues) for similar problems
2. Review the [README](README.md) for updated documentation
3. Open a new issue if your problem hasn't been addressed

---

## Previous Upgrades

### Upgrading from v3.x to v4.x

For v3.x to v4.x upgrade instructions, please refer to the [v4.0.0 release notes](https://github.com/daikazu/laratone/releases/tag/v4.0.0).

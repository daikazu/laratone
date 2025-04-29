# Laratone

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laratone.svg?style=flat-square)](https://packagist.org/packages/daikazu/laratone)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laratone/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laratone/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laratone/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daikazu/laratone/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laratone.svg?style=flat-square)](https://packagist.org/packages/daikazu/laratone)

Laratone is a comprehensive Laravel package for managing color libraries and swatches in your applications. It provides an easy-to-use API for storing, retrieving, and managing color data, with built-in support for various color formats (HEX, RGB, CMYK, LAB) and popular color libraries.

## Features

- 🎨 Multiple built-in color libraries (Pantone, GuangShun Thread, HC Twill)
- 🔄 Automatic color data caching for improved performance
- 📦 Easy color book management and seeding
- 🔍 Flexible API for color searching and filtering
- 🛠️ Simple integration with Laravel applications
- 📝 Support for custom color books and formats

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or greater

## Installation

You can install the package via composer:

```bash
composer require daikazu/laratone
```

### Publish Configuration and Migrations

Publish the configuration file:

```bash
php artisan vendor:publish --tag="laratone-config"
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laratone-migrations"
php artisan migrate
```

## Configuration

The published config file (`config/laratone.php`) contains the following options:

```php
return [
    // Table prefix for Laratone tables
    'table_prefix' => 'laratone_',
    
    // Cache duration in seconds for color books and colors
    'cache_time' => 3600,
];
```

## Usage

### Seeding Color Books

Laratone comes with several pre-built color libraries:

- `ColorBookPlusSolidCoated`
- `ColorBookPlusSolidCoated336NewColors`
- `ColorBookMetallicCoated`
- `ColorBookPlusMetallicCoated`
- `GuangShunThreadColors`
- `HCTwillColors`

#### Seed All Color Books
```bash
php artisan laratone:seed
```

#### Seed Specific Color Books
```bash
php artisan laratone:seed ColorBookPlusSolidCoatedSeeder
```

#### Import Custom Color Books
```bash
php artisan laratone:seed --file ./mycolorbookfile.json
```

Example Color Book format:
```json
{
  "name": "My Custom Color Book",
  "data": [
    {
      "name": "Custom Color 1",
      "lab": "88.19,-6.97,111.73",
      "hex": "FEDD00",
      "rgb": "254,221,0",
      "cmyk": "0,1,100,0"
    }
  ]
}
```

## API

### Color Books

List all available color books:

```http
GET /api/laratone/colorbooks
```

| Parameter | Required | Description | Default |
|-----------|:--------:|-------------|:-------:|
| sort      | No       | Sort by name (asc/desc) | asc |

### Colors

Get colors from a specific color book:

```http
GET /api/laratone/colorbook/{color-book-slug}
```

| Parameter | Required | Description | Default |
|-----------|:--------:|-------------|:-------:|
| sort      | No       | Sort by name (asc/desc) | asc |
| limit     | No       | Limit number of results | - |
| random    | No       | Randomize results | false |

## Color Management

Laratone provides a simple API for managing colors programmatically:

```php
use Daikazu\Laratone\Laratone;

// Get all color books
$colorBooks = Laratone::colorBooks();

// Get a specific color book
$colorBook = Laratone::colorBookBySlug('color-book-plus-solid-coated');

// Create a new color book
$newColorBook = Laratone::createColorBook('My New Color Book');

// Add colors to a color book
$color = Laratone::addColorToBook($colorBook, [
    'name' => 'New Color',
    'hex' => 'FF0000',
    'rgb' => '255,0,0'
]);

// Update a color
Laratone::updateColor($color, ['name' => 'Updated Color Name']);

// Delete a color
Laratone::deleteColor($color);
```

## Caching

Laratone automatically caches color book and color data to improve performance. The cache duration can be configured in the config file. To clear the cache:

```php
Laratone::clearCache();
```

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

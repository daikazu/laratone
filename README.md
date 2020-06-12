# Laratone

[![GitHub Workflow Status](https://github.com/daikazu/laratone/workflows/Run%20tests/badge.svg)](https://github.com/daikazu/laratone/actions)
[![styleci](https://styleci.io/repos/CHANGEME/shield)](https://styleci.io/repos/127805076)

[![Packagist](https://img.shields.io/packagist/v/daikazu/laratone.svg)](https://packagist.org/packages/daikazu/laratone)
[![Packagist](https://poser.pugx.org/daikazu/laratone/d/total.svg)](https://packagist.org/packages/daikazu/laratone)
[![Packagist](https://img.shields.io/packagist/l/daikazu/laratone.svg)](https://packagist.org/packages/daikazu/laratone)
[![All Contributors](https://img.shields.io/badge/all_contributors-1-orange.svg?style=flat-square)](#contributors-)


Laravel Color Library Package

This is a simple package to manage and seed various color libraries I find my self using all time.

## Installation

Install via composer
```bash
composer require daikazu/laratone
```

### Publish config

```bash
php artisan vendor:publish --tag=laratone-config

```

You can change the table prefix name in the config to what ever you like.
```php
<?php

return [
    'table_prefix' => 'laratone_',
];

```

### Migrate Database table

```bash
php artisan migrate
```

### Seed with default Color Books

```bash
php artisan db:seed --class="Daikazu\Laratone\ColorBooks\PantonePlusSolidCoatedSeeder"
```

Four Color Book seeders have been made available for you;

- `PantonePlusSolidCoatedSeeder`
- `PantonePlusSolidCoated336NewColorsSeeder`
- `PantoneMetallicCoatedSeeder`
- `PantonePlusMetallicCoatedSeeder`
- `GuangShunThreadColorsSeeder`
- `HCTwillColorsSeeder`

## Usage

CHANGE ME

## Security

If you discover any security related issues, please email
instead of using the issue tracker.

## Contributors ✨

- [Mike Wall](https://github.com/daikazu)
- [All contributors](https://github.com/daikazu/laratone/graphs/contributors)


This project follows the [all-contributors](https://github.com/all-contributors/all-contributors) specification. Contributions of any kind welcome!

## License

Copyright © Mike Wall

Laratone is open-sourced software licensed under the [MIT license](LICENSE.md).

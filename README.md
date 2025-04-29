# Laratone

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laratone.svg?style=flat-square)](https://packagist.org/packages/daikazu/laratone)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laratone/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laratone/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laratone/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daikazu/laratone/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laratone.svg?style=flat-square)](https://packagist.org/packages/daikazu/laratone)

Laravel Color Library Package

This is a simple package to manage and seed various color libraries I find my self using all time. You can use the defaults or simple add your own.
Laratone provides a few routes to allow simple access via url.

## Installation

You can install the package via composer:

```bash
composer require daikazu/laratone
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laratone-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laratone-config"
```

This is the contents of the published config file:

You can change the table prefix name in the config to what ever you like.
```php
<?php

return [
    'table_prefix' => 'laratone_', //default
];

```


## Usage


### Seed with default Color Books

Use the Laratone artisan command to seed Color Books to your database.


The following Color Books have been made available for you;
- `ColorBookPlusSolidCoated`
- `ColorBookPlusSolidCoated336NewColors`
- `ColorBookMetallicCoated`
- `ColorBookPlusMetallicCoated`
- `GuangShunThreadColors`
- `HCTwillColors`

### Seed all Color Books in Package
```bash
php artisan laratone:seed
```

### Seed specific Color Books in Package
```bash
php artisan laratone:seed ColorBookPlusSolidCoatedSeeder
```


### Seed your own Color Books

```bash
php artisan laratone:seed --file ./mycolorbookfile.json
```

Example Color Book format
```json
{
  "name": "Pantone Plus Solid Coated",
  "data": [
    {
      "name": "Yellow C",
      "lab": "88.19,-6.97,111.73",
      "hex": "FEDD00",
      "rgb": "254,221,0",
      "cmyk": "0,1,100,0"
    },
    {
      "name": "Yellow 012 C",
      "lab": "86.69,-3.2,109.49",
      "hex": "FFD700",
      "rgb": "255,215,0",
      "cmyk": "0,2,98,0"
    }...
```

## API

### Color Books



```http request
http://example.test/api/laratone/colorbooks
```
| URL Parameter | Required | Description                              | Default |
|---------------|:--------:|------------------------------------------|:-------:|
| sort          |   false  | Sort Color Book By Name `asc` or `desc`  |   asc   |

#### Example:
```json
[
    {
        "name": "Color Book Plus Solid Coated 336 new Colors",
        "slug": "color-book-plus-solid-coated-336-new-colors"
    },
    {
        "name": "Color Book Plus Solid Coated",
        "slug": "color-book-plus-solid-coated"
    }...
]
```

### Colors

Return colors from ColorBook based on ColorBook slug ie.`color-book-plus-solid-coated`

```http request
http://example.test/api/laratone/colorbook/color-book-plus-solid-coated
```

| URL Parameter | Required | Description                                          | Default |   |
|---------------|:--------:|------------------------------------------------------|:-------:|---|
| sort          |   false  | Sort Colors By Name `asc` or `desc`                  |   asc   |   |
| limit         |   false  | Limit number of colors returned                      |    -    |   |
| random        |   false  | Set to `true` to randomize colors (overrides `sort`) |  false  |   |

#### Example:
```json
{
    "name": "Color Book Plus Solid Coated",
    "slug": "color-book-plus-solid-coated",
    "colors": [
        {
            "name": "100 C",
            "lab": null,
            "hex": "F6EB61",
            "rgb": null,
            "cmyk": null
        },
        {
            "name": "101 C",
            "lab": null,
            "hex": "F7EA48",
            "rgb": null,
            "cmyk": null
        }...
}
```


## TODO

- write tests (If someone would like to help with this please send a PR)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

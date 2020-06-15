# Laratone

[![GitHub Workflow Status](https://github.com/daikazu/laratone/workflows/Run%20tests/badge.svg)](https://github.com/daikazu/laratone/actions)
[![styleci](https://styleci.io/repos/CHANGEME/shield)](https://styleci.io/repos/127805076)

[![Packagist](https://img.shields.io/packagist/v/daikazu/laratone.svg)](https://packagist.org/packages/daikazu/laratone)
[![Packagist](https://poser.pugx.org/daikazu/laratone/d/total.svg)](https://packagist.org/packages/daikazu/laratone)
[![Packagist](https://img.shields.io/packagist/l/daikazu/laratone.svg)](https://packagist.org/packages/daikazu/laratone)
[![All Contributors](https://img.shields.io/badge/all_contributors-1-orange.svg?style=flat-square)](#contributors-)


Laravel Color Library Package

This is a simple package to manage and seed various color libraries I find my self using all time. You can use the defaults or simple add your own.
Laratone provides a few routes to allow simple access via url.

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
    'table_prefix' => 'laratone_', //default
];

```

### Migrate Database table

```bash
php artisan migrate
```

## Usage


### Seed with default Color Books

Use the Laratone artisan command to seed Color Books to your database.


The following Color Books have been made available for you;
- `PantonePlusSolidCoated`
- `PantonePlusSolidCoated336NewColors`
- `PantoneMetallicCoated`
- `PantonePlusMetallicCoated`
- `GuangShunThreadColors`
- `HCTwillColors`

### Seed all Color Books in Package
```bash
php artisan laratone:seed
```

### Seed specific Color Books in Package
```bash
php artisan laratone:seed PantonePlusSolidCoatedSeeder
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

##API

###Color Books



```http request
http://example.test/api/laratone/colorbooks
```
| URL Parameter | Required | Description                              | Default |
|---------------|:--------:|------------------------------------------|:-------:|
| sort          |   false  | Sort Color Book By Name `asc` or `desc`  |   asc   |

####Example:
```json
[
    {
        "name": "Pantone Plus Solid Coated 336 new Colors",
        "slug": "pantone-plus-solid-coated-336-new-colors"
    },
    {
        "name": "Pantone Plus Solid Coated",
        "slug": "pantone-plus-solid-coated"
    }...
]
```

###Colors

Return colors from ColorBook based on ColorBook slug ie.`pantone-plus-solid-coated`

```http request
http://example.test/api/laratone/colorbook/pantone-plus-solid-coated
```

| URL Parameter | Required | Description                                          | Default |   |
|---------------|:--------:|------------------------------------------------------|:-------:|---|
| sort          |   false  | Sort Colors By Name `asc` or `desc`                  |   asc   |   |
| limit         |   false  | Limit number of colors returned                      |    -    |   |
| random        |   false  | Set to `true` to randomize colors (overrides `sort`) |  false  |   |

####Example:
```json
{
    "name": "Pantone Plus Solid Coated",
    "slug": "pantone-plus-solid-coated",
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


##TODO

- write tests (If someone would like to help with this please send a PR)


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

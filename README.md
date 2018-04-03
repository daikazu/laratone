# laratone
Laravel Color Library Package

## Install

```bash
composer require daikazu\laratone
```

### public config

```bash
php artisan vendor:publish --tag=laratone-config

```

You can change the table prefix name in the config to what ever you like.


# Migrate Database table

```bash
php artisan migrate
```

### Seed with default Color Books

```
php artisan db:seed --class="Daikazu\Laratone\Colorbooks\PantonePlusSolidCoatedSeeder"
```

Four Colorbook seeders have been made available for you;

- `PantonePlusSolidCoatedSeeder`
- `PantonePlusSolidCoated336NewColorsSeeder`
- `PantoneMetallicCoatedSeeder`
- `PantonePlusMetallicCoatedSeeder`


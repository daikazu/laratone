# laratone
Laravel Color Library Package

## Install

```bash
composer require daikazu\laratone
```

### publish config

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

```
php artisan db:seed --class="Daikazu\Laratone\Colorbooks\PantonePlusSolidCoatedSeeder"
```

Four Color Book seeders have been made available for you;

- `PantonePlusSolidCoatedSeeder`
- `PantonePlusSolidCoated336NewColorsSeeder`
- `PantoneMetallicCoatedSeeder`
- `PantonePlusMetallicCoatedSeeder`


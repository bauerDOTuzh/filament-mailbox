# Under Active development - use on your own risk
# Filament plugin to add sandbox banner to all laravel emails

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bauerdot/filament-mailbox.svg?style=flat-square)](https://packagist.org/packages/bauerdot/filament-mailbox)
![GitHub Tests Action Status](https://github.com/BauerdotUZH/filament-mailbox/actions/workflows/run-tests.yml/badge.svg)
![GitHub Code Style Action Status](https://github.com/BauerdotUZH/filament-mailbox/actions/workflows/fix-php-code-style-issues.yml/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/bauerdot/filament-mailbox.svg?style=flat-square)](https://packagist.org/packages/bauerdot/filament-mailbox)

This plugin adds, based on managable config in Filament admin panel, a banner to all outgoing emails. Additionally, this package includes small preview of all outgoing emails in Filament admin panel, this past is inspured by TappNetwork package.

## Installation

You can install the package via composer:


```bash
composer require bauerdot/filament-mailbox
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-mailbox-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-mailbox-config"
```


Optionally, you can publish the translations files with:

```bash
php artisan vendor:publish --tag="filament-mailbox-translations"
```

## Using the Resource

Add this plugin to a panel on `plugins()` method. 
E.g. in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Bauerdot\FilamentMailBox\FilamentMailBoxPlugin;
 
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentMailBoxPlugin::make(),
            //...
        ]);
}
```

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

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

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

## Using env variables (fastest start/deterministic for envs)

all emails can be types in example@example.com, example@example.com...

MAILBOX_SHOW_ENV_BANNER=true //show banner on all emails
MAILBOX_SANDBOX_MODE=true //if sandbox mode all emails are redirected to sandbox address
MAILBOX_ALLOWED_EMAILS=example@example.com //which emails are allowed to be send out in sandbox mode
MAILBOX_SANDBOX_ADDRESS=example@example.com //all emails are redirected to this address in sandbox mode
MAILBOX_BCC_ADDRESS=example@example.com //bcc all outgoing emails


And much more however it is encouraged to use them via config in admin panel.

When you dont lock values in config and try to define it is encourages to run to clear temporary cahce
`php artisan filament-mailbox:clear-mail-settings-cache`

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


Next steps
1) Add database periodic cleanups (of bodies etc.)
3) add proper testing for bcc 
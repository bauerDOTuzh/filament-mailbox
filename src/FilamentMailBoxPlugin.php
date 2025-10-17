<?php

namespace Bauerdot\FilamentMailBox;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMailBoxPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-mailbox';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources(
                [
                    \Bauerdot\FilamentMailBox\Resources\MailSettingResource::class,
                    \Bauerdot\FilamentMailBox\Resources\MailLogResource::class,
                ]
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

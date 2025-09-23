<?php

namespace Bauerdot\FilamentMailLog;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMailLogPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-maillog';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources(
                [
                    \Bauerdot\FilamentMailLog\Resources\MailSettingResource::class,
                    \Bauerdot\FilamentMailLog\Resources\MailLogResource::class,
                    // \Bauerdot\FilamentMailLog\Resources\MailLogResource::class,
                ]
                // config('filament-maillog.resources')
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

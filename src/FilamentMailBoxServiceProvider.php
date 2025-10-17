<?php

namespace Bauerdot\FilamentMailBox;

use Bauerdot\FilamentMailBox\Listeners\MessageSendingListener;
use Illuminate\Mail\Events\MessageSending;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMailBoxServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-mailbox')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_filament_mail_log_table')
            ->hasMigration('create_mail_setting_table');
    }

    public function packageBooted(): void
    {

        // Register the delegating mail message sending listener. It will delegate
        // to the focused listeners internally. We register only this one to avoid
        // duplicate handling (don't register the split listeners directly).
        $this->app['events']->listen(MessageSending::class, MessageSendingListener::class);
    }
}

<?php

namespace Bauerdot\FilamentMailLog;

use Bauerdot\FilamentMailLog\Events\MailLogEventHandler;
use Bauerdot\FilamentMailLog\Listeners\MessageSendingListener;
use Illuminate\Mail\Events\MessageSending;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMailLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-maillog')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_filament_mail_log_table')
            ->hasMigration('create_mail_setting_table');
    }

    public function packageBooted(): void
    {
        $this->app['events']->subscribe(MailLogEventHandler::class);

        // Register mail message sending listener
        $this->app['events']->listen(MessageSending::class, MessageSendingListener::class);
    }
}

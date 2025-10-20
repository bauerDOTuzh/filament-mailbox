<?php

namespace Bauerdot\FilamentMailBox\Console;

use Bauerdot\FilamentMailBox\Models\MailSetting;
use Illuminate\Console\Command;

class ClearMailSettingsCache extends Command
{
    protected $signature = 'filament-mailbox:clear-mail-settings-cache';

    protected $description = 'Clear cached mail settings and DTO cache for filament-mailbox.';

    public function handle(): int
    {
        MailSetting::flushCache();

        $this->info('Filament Mailbox: mail settings cache cleared.');

        return self::SUCCESS;
    }
}

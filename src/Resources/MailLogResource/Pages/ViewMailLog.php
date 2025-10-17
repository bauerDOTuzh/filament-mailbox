<?php

namespace Bauerdot\FilamentMailBox\Resources\MailLogResource\Pages;

use Bauerdot\FilamentMailBox\Resources\MailLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Actions\ResendMail;

class ViewMailLog extends ViewRecord
{
    protected static string $resource = MailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Bauerdot\FilamentMailBox\Resources\MailLogResource\Actions\ResendMailAction::make(),
        ];
    }

    protected function getResendBannerHtml(): string
    {
        $env = app()->environment();
        $appName = config('app.name', 'Laravel');
        $server = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $originalTo = implode(', ', array_keys($this->record->to ?? []));
        $originalCc = implode(', ', array_keys($this->record->cc ?? []));
        $originalBcc = implode(', ', array_keys($this->record->bcc ?? []));

        return "<div style='padding:10px;border:1px solid #f1c40f;background:#fffbe6;color:#8a6d00;border-radius:4px;'>⚠️ Test resend: {$appName} - {$env} <br/><strong>Original To:</strong> {$originalTo} <br/><strong>Original CC:</strong> {$originalCc}</div>";
    }
}

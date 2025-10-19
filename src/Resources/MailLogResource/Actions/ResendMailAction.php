<?php

namespace Bauerdot\FilamentMailBox\Resources\MailLogResource\Actions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Facades\Log;
use Bauerdot\FilamentMailBox\Actions\ResendMail;

class ResendMailAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'resend_mail_action';
    }



    protected function setUp(): void
    {
        parent::setUp();

        $this->label(false)
            ->icon('heroicon-o-arrow-path')
            ->iconSize(IconSize::Medium)
            ->tooltip(__('filament-mailbox::filament-mailbox.resend_email_heading'))
            ->modalHeading(__('filament-mailbox::filament-mailbox.resend_email_heading'))
            ->modalWidth('2xl')
            ->schema([
                TagsInput::make('to')
                    ->label(__('filament-mailbox::filament-mailbox.to'))
                    ->placeholder(__('filament-mailbox::filament-mailbox.insert_multiple_email_placelholder'))
                    ->nestedRecursiveRules(['email'])
                    ->default(fn ($record): array => ! empty($record->to) ? array_values(array_filter(array_map('trim', explode(',', $record->to)))) : [])
                    ->required(),
                TagsInput::make('cc')
                    ->label(__('filament-mailbox::filament-mailbox.cc'))
                    ->placeholder(__('filament-mailbox::filament-mailbox.insert_multiple_email_placelholder'))
                    ->nestedRecursiveRules(['email'])
                    ->default(fn ($record): array => ! empty($record->cc) ? array_values(array_filter(array_map('trim', explode(',', $record->cc)))) : []),
                TagsInput::make('bcc')
                    ->label(__('filament-mailbox::filament-mailbox.bcc'))
                    ->placeholder(__('filament-mailbox::filament-mailbox.insert_multiple_email_placelholder'))
                    ->nestedRecursiveRules(['email'])
                    ->default(fn ($record): array => ! empty($record->bcc) ? array_values(array_filter(array_map('trim', explode(',', $record->bcc)))) : []),
                Radio::make('attachments')
                    ->label(__('filament-mailbox::filament-mailbox.add_attachments'))
                    ->boolean()
                    ->inline()
                    ->inlineLabel(false)
                    // only show the radio when attachments exist
                    ->visible(fn ($record): bool => ! empty($record->attachments))
                    ->default(fn ($record): bool => ! empty($record->attachments))
                    ->required(),
                Placeholder::make('no_attachments')
                    ->content(fn () => __('filament-mailbox::filament-mailbox.no_attachments_available'))
                    ->visible(fn ($record): bool => empty($record->attachments)),
            ])
            ->action(function ($record, array $data) {
                try {
                    $to = $data['to'] ?? [];
                    $cc = $data['cc'] ?? [];
                    $bcc = $data['bcc'] ?? [];
                    $includeAttachments = $data['attachments'] ?? false;

                    (new ResendMail())->handle($record, $to, $cc, $bcc, $includeAttachments);

                    Notification::make()
                        ->title(__('filament-mailbox::filament-mailbox.resend_email_success'))
                        ->success()
                        ->duration(5000)
                        ->send();

                } catch (\Throwable $e) {
                    Log::error($e->getMessage());
                    Notification::make()
                        ->title(__('filament-mailbox::filament-mailbox.resend_email_error'))
                        ->danger()
                        ->duration(5000)
                        ->send();
                }
            });
    }
}

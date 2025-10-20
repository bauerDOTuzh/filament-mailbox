<?php

namespace Bauerdot\FilamentMailBox\Resources\MailSettingResource\Pages;

use Bauerdot\FilamentMailBox\Models\MailSetting;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Bauerdot\FilamentMailBox\Resources\MailSettingResource;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EditMailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MailSettingResource::class;

    // 1. Define the Blade view for this page (use package view namespace)
    protected string $view = 'filament-mailbox::filament.mail-settings.pages.edit-mail-settings';

    // 2. This public property will hold the form's state
    public ?array $data = [];

    public function mount(): void
    {
        // 3. Fill the form with initial data from DTO
        $dto = MailSettingsDto::fromConfigAndModel();

        $this->form->fill([
            'show_environment_banner' => $dto->show_environment_banner,
            'sandbox_mode' => $dto->sandbox_mode,
            'sandbox_address' => $dto->sandbox_address,
            'bcc_address' => $dto->bcc_address,
            'allowed_emails' => $dto->allowed_emails,
        ]);
    }

    // 4. Define the entire form in this method
    public function form(Schema $form): Schema
    {
        $lock = config('filament-mailbox.mail_settings.lock_values', false);
        $defaults = config('filament-mailbox.mail_settings.defaults', []);

        $mailer = $this->getMailerInfo();

        // Precompute values to avoid closures that might access component container early
        $transportState = $mailer['transport'] ?? 'unknown';
        $connectionHint = $mailer['note'] ? __('filament-mailbox::filament-mailbox.banner.connection', ['conn' => $mailer['note']]) : null;

        $lockedShowEnvironmentBanner = $lock && array_key_exists('show_environment_banner', $defaults);
        $lockedSandboxMode = $lock && array_key_exists('sandbox_mode', $defaults);
        $lockedSandboxAddress = $lock && array_key_exists('sandbox_address', $defaults);
        $lockedBccAddress = $lock && array_key_exists('bcc_address', $defaults);
        $lockedAllowedEmails = $lock && array_key_exists('allowed_emails', $defaults);

        return $form
            ->schema([
                // Read-only mailer / connection info (use Placeholder for pure display)
                TextEntry::make('mail_transport')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.current_mail_transport'))
                    ->state($transportState)
                    ->hint($connectionHint),

                TextEntry::make('supports_stats')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.delivery_stats_supported'))
                    ->state(MailSettingsDto::fromConfigAndModel()->supports_stats ? __('filament-mailbox::filament-mailbox.yes') : __('filament-mailbox::filament-mailbox.no')),

                TextEntry::make('track_opens')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.track_opens'))
                    ->state(MailSettingsDto::fromConfigAndModel()->tracking_on ? __('filament-mailbox::filament-mailbox.yes') : __('filament-mailbox::filament-mailbox.no'))
                    ->hint(MailSettingsDto::fromConfigAndModel()->tracking_level),

                Components\Toggle::make('show_environment_banner')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.show_environment_banner'))
                    ->disabled($lockedShowEnvironmentBanner)
                    ->hint($lockedShowEnvironmentBanner ? __('filament-mailbox::filament-mailbox.hints.locked_in_config') : null),

                Components\Toggle::make('sandbox_mode')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.sandbox_mode'))
                    ->disabled($lockedSandboxMode)
                    ->hint($lockedSandboxMode ? __('filament-mailbox::filament-mailbox.hints.locked_in_config') : null),

                Components\TextInput::make('sandbox_address')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.sandbox_address'))
                    ->email()
                    ->placeholder(__('filament-mailbox::filament-mailbox.placeholders.example_email'))
                    ->columnSpanFull()
                    ->disabled($lockedSandboxAddress)
                    ->hint($lockedSandboxAddress ? __('filament-mailbox::filament-mailbox.hints.locked_in_config') : null),

                Components\TagsInput::make('bcc_address')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.bcc_address'))
                    ->placeholder(__('filament-mailbox::filament-mailbox.placeholders.example_email'))
                    ->disabled($lockedBccAddress)
                    ->hint($lockedBccAddress ? __('filament-mailbox::filament-mailbox.hints.locked_in_config') : __('filament-mailbox::filament-mailbox.hints.bcc_help')),

                Components\TagsInput::make('allowed_emails')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.allowed_emails'))
                    ->placeholder(__('filament-mailbox::filament-mailbox.placeholders.example_email'))
                    ->disabled($lockedAllowedEmails)
                    ->hint($lockedAllowedEmails ? __('filament-mailbox::filament-mailbox.hints.locked_in_config') : __('filament-mailbox::filament-mailbox.hints.allowed_help')),
            ])
            ->statePath('data'); // Link the form to the $data property
    }

    /**
     * Determine mailer/transport and best-effort remote IP/connection info.
     * Returns an array with keys: transport, connection, remote_ip, note
     */
    private function getMailerInfo(): array
    {
        try {
            $mailerName = config('mail.default') ?? 'default';
            $mailers = config('mail.mailers', []);
            $mailer = $mailers[$mailerName] ?? [];

            $transport = $mailer['transport'] ?? $mailerName ?? 'unknown';

            $connection = null;
            $note = null;

            if ($transport === 'smtp' || $transport === 'smtp+tls' || $transport === 'smtp+ssl') {
                $host = $mailer['host'] ?? null;
                $port = $mailer['port'] ?? null;
                if ($host) {
                    $note = $host.($port ? ":{$port}" : '');
                }

            } elseif ($transport === 'log') {
                // show log channel if configured
                $channel = $mailer['channel'] ?? config('logging.channels.mail') ?? config('logging.default');
                $note = 'log'.($channel ? " (channel: {$channel})" : '');
                $note .= __('filament-mailbox::filament-mailbox.hints.using_log_transport');
            } else {
                // generic fallback: display mailer config summary
                if (! empty($mailer)) {
                    $connectionParts = [];
                    foreach (['host', 'port', 'username', 'channel'] as $k) {
                        if (isset($mailer[$k])) {
                            $connectionParts[] = "{$k}=".$mailer[$k];
                        }
                    }
                    $note = $connectionParts ? implode(', ', $connectionParts) : null;
                }
            }

            return [
                'transport' => $transport,
                'note' => $note,
            ];
        } catch (\Throwable $e) {
            return [
                'transport' => 'unknown',
                'note' => null,
            ];
        }
    }

    /**
     * Return an array of keys that are locked in config (if lock is enabled)
     */
    private function lockedKeys(): array
    {
        $lock = config('filament-mailbox.mail_settings.lock_values', false);
        $defaults = config('filament-mailbox.mail_settings.defaults', []);

        return $lock ? array_keys($defaults) : [];
    }

    private function isLocked(string $key): bool
    {
        return in_array($key, $this->lockedKeys(), true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label(__('filament-mailbox::filament-mailbox.actions.send_test_email'))
                ->modalHeading(__('filament-mailbox::filament-mailbox.actions.send_test_email_heading'))
                ->modalSubmitActionLabel(__('filament-mailbox::filament-mailbox.actions.send'))
                ->schema([ // In v4, use form() instead of schema() for modal actions
                    Components\TextInput::make('testEmailRecipient')
                        ->label(__('filament-mailbox::filament-mailbox.actions.recipient'))
                        ->email()
                        ->placeholder(__('filament-mailbox::filament-mailbox.placeholders.example_email')),
                ])
                ->action(function (array $data): void {
                    $dto = MailSettingsDto::fromConfigAndModel();
                    $authUser = Auth::user();
                    $authEmail = $authUser ? data_get($authUser, 'email') : null;
                    $recipient = $data['testEmailRecipient'] ?? $authEmail ?? $dto->sandbox_address ?? config('mail.from.address');

                    if (empty($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        Notification::make()
                            ->danger()
                            ->title(__('filament-mailbox::filament-mailbox.notifications.no_recipient'))
                            ->send();

                        return;
                    }

                    try {
                        Mail::raw(__('filament-mailbox::filament-mailbox.notifications.test_email_body'), function ($m) use ($recipient) {
                            $m->to($recipient)->subject(__('filament-mailbox::filament-mailbox.notifications.test_email_subject'));
                        });

                        Notification::make()->success()->title(__('filament-mailbox::filament-mailbox.notifications.test_sent_title'))->body(__('filament-mailbox::filament-mailbox.notifications.test_sent_body', ['recipient' => $recipient]))->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title(__('filament-mailbox::filament-mailbox.notifications.test_failed_title'))->body($e->getMessage())->send();
                    }
                }),
        ];
    }

    // 6. This method is called by the 'save' action's submit()
    public function save(): void
    {
        $data = $this->form->getState();

        $lock = config('filament-mailbox.mail_settings.lock_values', false);
        $defaults = config('filament-mailbox.mail_settings.defaults', []);

        foreach ($data as $key => $value) {
            if ($lock && array_key_exists($key, $defaults)) {
                // Skip saving locked default keys
                continue;
            }

            MailSetting::setValue($key, $value);
        }

        // Ensure cache is refreshed for all settings
        MailSetting::flushCache();

        Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->send();
    }

    protected function getSettings(): array
    {
        // Backwards compatible fallback: return the merged values
        return MailSetting::allCached();
    }
}

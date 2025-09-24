<?php

namespace Bauerdot\FilamentMailBox\Resources\MailSettingResource\Pages;

use Bauerdot\FilamentMailBox\Models\MailSetting;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Bauerdot\FilamentMailBox\Resources\MailSettingResource;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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

        return $form
            ->schema([
                Components\Toggle::make('show_environment_banner')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.show_environment_banner') ?? 'Show environment banner')
                    ->disabled(fn () => $lock && array_key_exists('show_environment_banner', $defaults))
                    ->hint(fn () => $lock && array_key_exists('show_environment_banner', $defaults) ? __('This value is locked in config') : null),

                Components\Toggle::make('sandbox_mode')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.sandbox_mode') ?? 'Sandbox mode')
                    ->disabled(fn () => $lock && array_key_exists('sandbox_mode', $defaults))
                    ->hint(fn () => $lock && array_key_exists('sandbox_mode', $defaults) ? __('This value is locked in config') : null),

                Components\TextInput::make('sandbox_address')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.sandbox_address') ?? 'Sandbox address')
                    ->email()
                    ->placeholder('test@example.com')
                    ->columnSpanFull()
                    ->disabled(fn () => $lock && array_key_exists('sandbox_address', $defaults))
                    ->hint(fn () => $lock && array_key_exists('sandbox_address', $defaults) ? __('This value is locked in config') : null),

                Components\TagsInput::make('bcc_address')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.bcc_address') ?? 'BCC addresses')
                    ->placeholder('someone@example.com')
                    ->hint('Separate multiple addresses with commas. Invalid addresses will be ignored.')
                    ->disabled(fn () => $lock && array_key_exists('bcc_address', $defaults))
                    ->hint(fn () => $lock && array_key_exists('bcc_address', $defaults) ? __('This value is locked in config') : 'Separate multiple addresses with commas. Invalid addresses will be ignored.'),

                Components\TagsInput::make('allowed_emails')
                    ->label(__('filament-mailbox::filament-mailbox.navigation.settings.allowed_emails') ?? 'Allowed emails')
                    ->placeholder('allowed@example.com')
                    ->hint('Separate multiple addresses with commas. Only valid addresses will be used.')
                    ->disabled(fn () => $lock && array_key_exists('allowed_emails', $defaults))
                    ->hint(fn () => $lock && array_key_exists('allowed_emails', $defaults) ? __('This value is locked in config') : 'Separate multiple addresses with commas. Only valid addresses will be used.'),
            ])
            ->statePath('data'); // Link the form to the $data property
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
                        ->placeholder('test@example.com'),
                ])
                ->action(function (array $data): void {
                    $dto = MailSettingsDto::fromConfigAndModel();
                    $recipient = $data['testEmailRecipient'] ?? Auth::user()?->email ?? $dto->sandbox_address ?? config('mail.from.address');

                    if (empty($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        Notification::make()
                            ->danger()
                            ->title('No recipient available to send test email')
                            ->send();

                        return;
                    }

                    try {
                        Mail::raw('This is a test email from Filament Mail Log plugin.', function ($m) use ($recipient) {
                            $m->to($recipient)->subject('Filament Mail Log — Test Message');
                        });

                        Notification::make()->success()->title('Test email sent')->body("Sent to: {$recipient}")->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Failed to send test email')->body($e->getMessage())->send();
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

<?php

use Bauerdot\FilamentMailBox\Models\MailSettingsDto;

it('reads settings from config and provides defaults', function () {
    // Ensure mail_settings table exists for MailSettingsDto to query
    $migration = include __DIR__ . '/../database/migrations/create_mail_setting_table.php.stub';
    $migration->up();

    config()->set('filament-mailbox', [
        'mail_settings' => [
            'defaults' => [
                'sandbox_mode' => true,
                'sandbox_address' => 'test@example.com',
                'allowed_emails' => ['allowed@example.com'],
                'bcc_address' => 'bcc@example.com',
            ],
            'cache_ttl' => null,
        ],
    ]);

    // Flush cached DTO if any
    \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();

    $dto = MailSettingsDto::fromConfigAndModel(useCache: false);

    expect($dto->sandbox_mode)->toBeTrue();
    expect($dto->sandbox_address)->toBe('test@example.com');
    expect(is_array($dto->allowed_emails))->toBeTrue();
    expect($dto->bcc_address)->toBe(['bcc@example.com']);
});

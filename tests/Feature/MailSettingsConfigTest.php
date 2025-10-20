<?php

use Bauerdot\FilamentMailBox\Models\MailSettingsDto;

it('parses bcc and allowed_emails from defaults and validates addresses', function () {
    // Ensure mail_settings table exists (MailSettingsDto pulls MailSetting::allCached())
    $m = include __DIR__ . '/../../database/migrations/create_mail_setting_table.php.stub';
    $m->up();

    config()->set('filament-mailbox.mail_settings.defaults', [
        'bcc_address' => 'one@example.com, invalid, two@example.com',
        'allowed_emails' => 'allowed@example.com,another@x',
    ]);

    $dto = MailSettingsDto::fromConfigAndModel(useCache: false);

    expect($dto->bcc_address)->toBeArray();
    expect(in_array('one@example.com', $dto->bcc_address))->toBeTrue();
    expect(in_array('two@example.com', $dto->bcc_address))->toBeTrue();
    expect(count($dto->bcc_address))->toBe(2);

    expect($dto->allowed_emails)->toBeArray();
    expect(in_array('allowed@example.com', $dto->allowed_emails))->toBeTrue();
});

it('sets supports_stats false for smtp or log mail drivers', function () {
    $m = include __DIR__ . '/../../database/migrations/create_mail_setting_table.php.stub';
    $m->up();

    config()->set('mail.default', 'smtp');
    $dto = MailSettingsDto::fromConfigAndModel(useCache: false);
    expect($dto->supports_stats)->toBeFalse();

    config()->set('mail.default', 'log');
    $dto2 = MailSettingsDto::fromConfigAndModel(useCache: false);
    expect($dto2->supports_stats)->toBeFalse();

    config()->set('mail.default', 'sendmail');
    $dto3 = MailSettingsDto::fromConfigAndModel(useCache: false);
    expect($dto3->supports_stats)->toBeTrue();
});

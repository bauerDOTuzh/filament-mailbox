<?php

use Bauerdot\FilamentMailBox\Models\MailSetting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Ensure mail_settings table exists
    $m = include __DIR__.'/../../database/migrations/create_mail_setting_table.php.stub';
    $m->up();
    Cache::flush();
});

it('stores and retrieves typed values and caches them', function () {
    MailSetting::setValue('test_bool', true);
    MailSetting::setValue('test_array', ['a' => 1, 'b' => 2]);

    $v1 = MailSetting::getValue('test_bool');
    $v2 = MailSetting::getValue('test_array');

    expect($v1)->toBeTrue();
    expect(is_array($v2))->toBeTrue();
    expect($v2['a'])->toBe(1);

    // Ensure cache was populated
    $cacheKey = 'filament-mailbox.mail_settings.test_bool';
    expect(Cache::has($cacheKey))->toBeTrue();
});

it('respects lock_values when setting values', function () {
    config()->set('filament-mailbox.mail_settings.lock_values', true);
    config()->set('filament-mailbox.mail_settings.defaults', ['locked' => 'default']);

    // Try to set locked value
    MailSetting::setValue('locked', 'new');

    // Should still return default
    $val = MailSetting::getValue('locked');
    expect($val)->toBe('default');
});

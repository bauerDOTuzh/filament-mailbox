<?php

use Bauerdot\FilamentMailBox\Events\MailLogEventHandler;
use Bauerdot\FilamentMailBox\Listeners\EnvironmentBannerListener;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mime\Email as SymfonyEmail;

beforeEach(function () {
    $migration = include __DIR__ . '/../../database/migrations/create_filament_mail_log_table.php.stub';
    $migration->up();
    $migration2 = include __DIR__ . '/../../database/migrations/create_mail_setting_table.php.stub';
    $migration2->up();
});

it('does not allow mass-assigning status via create', function () {
    $payload = [
        'from' => 'a@a.com',
        'to' => 'b@b.com',
        'subject' => 'mass assign test',
        'body' => 'x',
        'text_body' => '',
        'message_id' => (string) Str::uuid(),
        'status' => 'bounced',
    ];

    $model = MailLog::create($payload);

    // status should not be mass-assignable; since no timestamps were set, status remains null
    expect($model->status)->toBeNull();
});

it('adds unique-id header to message when logging', function () {
    $email = new SymfonyEmail();
    $email->subject('header test');
    $email->text('hi');
    $email->from('x@x.com');
    $email->to('y@y.com');

    $handler = new MailLogEventHandler();
    $handler->handleMessageSending(new MessageSending($email));

    // header should be present on the message
    $header = $email->getHeaders()->get('unique-id');
    expect($header)->not->toBeNull();
    // and a corresponding MailLog should exist with that message_id
    $msgId = $header->getBodyAsString();
    expect(MailLog::where('message_id', $msgId)->exists())->toBeTrue();
});

it('injects environment banner into html body when enabled', function () {
    $email = new SymfonyEmail();
    $email->text('plain text body');
    $email->from('env@local');
    $email->to('me@local');

    // Ensure the banner setting is enabled by creating a MailSetting record
    if (class_exists(\Bauerdot\FilamentMailBox\Models\MailSetting::class)) {
        \Bauerdot\FilamentMailBox\Models\MailSetting::setValue('show_environment_banner', true);
    } else {
        config()->set('filament-mailbox.mail_settings.defaults.show_environment_banner', true);
    }

    if (class_exists(\Bauerdot\FilamentMailBox\Models\MailSettingsDto::class)) {
        \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();
    }

    // The EnvironmentBannerListener will add an HTML banner when enabled
    $listener = new EnvironmentBannerListener();
    $listener->handle(new MessageSending($email));

    $html = $email->getHtmlBody();
    // There should be some HTML content (banner + maybe transformed text)
    expect($html)->toBeString();
    expect(strlen($html))->toBeGreaterThan(0);
    // banner will contain the environment name (e.g., 'testing')
    expect(stripos($html, app()->environment()) !== false)->toBeTrue();
});

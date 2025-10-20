<?php

use Bauerdot\FilamentMailBox\Listeners\MailLoggingListener;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Bauerdot\FilamentMailBox\Models\MailLog;

beforeEach(function () {
    // Ensure mail_logs table exists
    $migration = include __DIR__ . '/../../database/migrations/create_mail_log_table.php.stub';
    $migration->up();
    $m2 = include __DIR__ . '/../../database/migrations/create_mail_setting_table.php.stub';
    $m2->up();
});

it('applies global bcc from config when provided as string or array', function () {
    config()->set('filament-mailbox.mail_settings.defaults', [
        'bcc_address' => 'bcc@example.com',
    ]);

    \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();

    $email = new SymfonyEmail();
    $email->from('from@example.com');
    $email->to('to@example.com');
    $email->subject('Test');

    $listener = new MailLoggingListener();
    $listener->handle(new MessageSending($email));

    // Message should have BCC header set
    $bcc = $email->getBcc();
    expect(is_array($bcc))->toBeTrue();
    expect(count($bcc))->toBeGreaterThan(0);

    // MailLog should be created and include the bcc address in the db
    $log = MailLog::first();
    expect($log)->not->toBeNull();
    expect(str_contains($log->bcc ?? '', 'bcc@example.com'))->toBeTrue();
});

it('redirects recipients to sandbox address and prefixes subject when recipients not allowed', function () {
    config()->set('filament-mailbox.mail_settings.defaults', [
        'sandbox_mode' => true,
        'sandbox_address' => 'sandbox@example.com',
        'allowed_emails' => ['allowed@example.com'],
    ]);

    \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();

    $email = new SymfonyEmail();
    $email->from('from@example.com');
    $email->to('blocked@example.com');
    $email->subject('Original Subject');
    $email->text('text');

    $listener = new MailLoggingListener();
    $listener->handle(new MessageSending($email));

    // X-Original-To header should be present
    $headers = $email->getHeaders();
    $orig = $headers->get('x-original-to');
    expect($orig)->not->toBeNull();

    // Recipient should be sandbox address
    $tos = array_map(fn($a) => $a->getAddress(), $email->getTo());
    expect(in_array('sandbox@example.com', $tos))->toBeTrue();

    // Subject should be prefixed with environment indicator
    expect(str_contains($email->getSubject(), '[TEST -'))->toBeTrue();
});

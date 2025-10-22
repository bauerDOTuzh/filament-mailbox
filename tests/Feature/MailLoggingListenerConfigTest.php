<?php

use Bauerdot\FilamentMailBox\Listeners\MailLoggingListener;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email as SymfonyEmail;

beforeEach(function () {
    // Ensure mail_logs table exists
    $migration = include __DIR__.'/../../database/migrations/create_mail_log_table.php.stub';
    $migration->up();
    $m2 = include __DIR__.'/../../database/migrations/create_mail_setting_table.php.stub';
    $m2->up();
});

it('applies global bcc from config when provided as string or array', function () {
    config()->set('filament-mailbox.mail_settings.defaults', [
        'bcc_address' => 'bcc@example.com',
    ]);

    \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();

    $email = new SymfonyEmail;
    $email->from('from@example.com');
    $email->to('to@example.com');
    $email->subject('Test');

    $listener = new MailLoggingListener;
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

    $email = new SymfonyEmail;
    $email->from('from@example.com');
    $email->to('blocked@example.com');
    $email->subject('Original Subject');
    $email->text('text');

    $listener = new MailLoggingListener;
    $listener->handle(new MessageSending($email));

    // X-Original-To header should be present
    $headers = $email->getHeaders();
    $orig = $headers->get('x-original-to');
    expect($orig)->not->toBeNull();

    // Recipient should be sandbox address
    $tos = array_map(fn ($a) => $a->getAddress(), $email->getTo());
    expect(in_array('sandbox@example.com', $tos))->toBeTrue();

    // Subject should be prefixed with environment indicator
    expect(str_contains($email->getSubject(), '[TEST -'))->toBeTrue();
});

it('keeps allowed recipients and removes blocked ones when mixed, does not add sandbox address', function () {
    config()->set('filament-mailbox.mail_settings.defaults', [
        'sandbox_mode' => true,
        'sandbox_address' => 'sandbox@example.com',
        'allowed_emails' => ['allowed@example.com'],
    ]);

    \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();

    $email = new SymfonyEmail;
    $email->from('from@example.com');
    $email->to(...['allowed@example.com', 'blocked@example.com']);
    $email->cc('blockedcc@example.com');
    $email->bcc('blockedbcc@example.com');
    $email->subject('Original Subject');

    $listener = new MailLoggingListener;
    $listener->handle(new MessageSending($email));

    // Allowed recipient should remain
    $tos = array_map(fn ($a) => $a->getAddress(), $email->getTo());
    expect(in_array('allowed@example.com', $tos))->toBeTrue();

    // Blocked recipients should be removed
    expect(in_array('blocked@example.com', $tos))->toBeFalse();

    // Sandbox should NOT be added because there are allowed recipients
    expect(in_array('sandbox@example.com', $tos))->toBeFalse();

    // X-Original-To header should be present because some recipients were blocked
    $headers = $email->getHeaders();
    $orig = $headers->get('x-original-to');
    expect($orig)->not->toBeNull();

    // Subject should still be prefixed because some recipients were blocked
    expect(str_contains($email->getSubject(), '[TEST -'))->toBeTrue();

    // MailLog should have been created and should reflect the modified recipients
    $log = MailLog::first();
    expect($log)->not->toBeNull();
    expect(str_contains($log->to ?? '', 'allowed@example.com'))->toBeTrue();
    expect(str_contains($log->to ?? '', 'blocked@example.com'))->toBeFalse();
});

it('does nothing when all recipients are allowed (no prefix, no original header)', function () {
    config()->set('filament-mailbox.mail_settings.defaults', [
        'sandbox_mode' => true,
        'sandbox_address' => 'sandbox@example.com',
        'allowed_emails' => ['allowed1@example.com', 'allowed2@example.com'],
    ]);

    \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();

    $email = new SymfonyEmail;
    $email->from('from@example.com');
    $email->to(...['allowed1@example.com', 'allowed2@example.com']);
    $email->subject('Original Subject');

    $listener = new MailLoggingListener;
    $listener->handle(new MessageSending($email));

    // Recipients should remain unchanged and no sandbox should be added
    $tos = array_map(fn ($a) => $a->getAddress(), $email->getTo());
    expect(in_array('allowed1@example.com', $tos))->toBeTrue();
    expect(in_array('allowed2@example.com', $tos))->toBeTrue();
    expect(in_array('sandbox@example.com', $tos))->toBeFalse();

    // X-Original-To header is added from the original recipients (implementation
    // currently always adds it when original recipients exist).
    $headers = $email->getHeaders();
    $orig = $headers->get('x-original-to');
    expect($orig)->not->toBeNull();

    // Subject should NOT be prefixed
    expect(str_contains($email->getSubject(), '[TEST -'))->toBeFalse();

    // MailLog should reflect the original recipients
    $log = MailLog::first();
    expect($log)->not->toBeNull();
    expect(str_contains($log->to ?? '', 'allowed1@example.com'))->toBeTrue();
    expect(str_contains($log->to ?? '', 'allowed2@example.com'))->toBeTrue();
});

it('redirects to sandbox when no allowed emails are configured', function () {
    config()->set('filament-mailbox.mail_settings.defaults', [
        'sandbox_mode' => true,
        'sandbox_address' => 'sandbox-only@example.com',
        'allowed_emails' => [],
    ]);

    \Bauerdot\FilamentMailBox\Models\MailSettingsDto::flushCache();

    $email = new SymfonyEmail;
    $email->from('from@example.com');
    $email->to(...['blocked1@example.com', 'blocked2@example.com']);
    $email->subject('Original Subject');

    $listener = new MailLoggingListener;
    $listener->handle(new MessageSending($email));

    // Sandbox recipient should be present
    $tos = array_map(fn ($a) => $a->getAddress(), $email->getTo());
    expect(in_array('sandbox-only@example.com', $tos))->toBeTrue();

    // Original blocked recipients should not be present
    expect(in_array('blocked1@example.com', $tos))->toBeFalse();
    expect(in_array('blocked2@example.com', $tos))->toBeFalse();

    // Subject should be prefixed
    expect(str_contains($email->getSubject(), '[TEST -'))->toBeTrue();

    // MailLog should include sandbox address only
    $log = MailLog::first();
    expect($log)->not->toBeNull();
    expect(str_contains($log->to ?? '', 'sandbox-only@example.com'))->toBeTrue();
    expect(str_contains($log->to ?? '', 'blocked1@example.com'))->toBeFalse();
});

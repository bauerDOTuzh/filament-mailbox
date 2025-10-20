<?php

use Bauerdot\FilamentMailBox\Enums\MailStatus;
use Bauerdot\FilamentMailBox\Events\MailLogEventHandler;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email as SymfonyEmail;

beforeEach(function () {
    // Run the mail_logs migration (the package provides a stub)
    $migration = include __DIR__.'/../../database/migrations/create_mail_log_table.php.stub';
    $migration->up();

    // Also ensure mail_settings table exists for MailSettingsDto to read
    $migration2 = include __DIR__.'/../../database/migrations/create_mail_setting_table.php.stub';
    $migration2->up();
});

it('saves an emitted email to the database', function () {
    // Ensure the migration actually created the table
    if (! Schema::hasTable('mail_logs')) {
        throw new \RuntimeException('mail_logs table does not exist after migration');
    }
    // Build a simple Symfony Email
    $email = new SymfonyEmail;
    $email->subject('Feature test email');
    $email->text('Hello test');
    $email->from('sender@example.com');
    $email->to('recipient@example.com');

    // Use the event handler to simulate the package handling
    $handler = new MailLogEventHandler;
    $handler->handleMessageSending(new MessageSending($email));

    // Assert a MailLog exists with our subject created by the listener
    $record = MailLog::where('subject', 'Feature test email')->first();
    expect($record)->toBeInstanceOf(MailLog::class);
    expect($record->message_id)->not->toBeNull();
    expect($record->sent_at)->toBeNull();

    $status = $record->status;
    // email is unsent it would wait for MessageSent event
    expect($status)->toBe(MailStatus::UNSENT);
});

it('respects status precedence when marking', function () {
    $log = MailLog::create([
        'from' => 'a@a.com',
        'to' => 'b@b.com',
        'subject' => 'precedence test',
        'body' => 'x',
        'text_body' => '',
        'message_id' => (string) Str::ulid(),
    ]);

    $log->markSent();
    $s = $log->status;
    expect($s)->toBe(MailStatus::SENT);

    $log->markDelivered();
    $log->refresh();
    $s = $log->status;
    expect($s)->toBe(MailStatus::DELIVERED);

    $log->markOpened();
    $log->refresh();
    $s = $log->status;
    expect($s)->toBe(MailStatus::OPENED);

    // A bounced event should take precedence over opened/delivered
    $log->markBounced();
    $log->refresh();
    $s = $log->status;
    expect($s)->toBe(MailStatus::BOUNCED);
});

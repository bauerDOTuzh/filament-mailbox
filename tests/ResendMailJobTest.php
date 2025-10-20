<?php

use Bauerdot\FilamentMailBox\Jobs\ResendMailJob;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    // Ensure mail_logs table exists for the factory
    $migration = include __DIR__.'/../database/migrations/create_mail_log_table.php.stub';
    $migration->up();
});

it('handles a MailLog instance without throwing', function () {
    Mail::fake();

    $log = MailLog::factory()->create();

    $job = new ResendMailJob($log);

    // Should not throw
    $job->handle();

    expect(true)->toBeTrue();
});

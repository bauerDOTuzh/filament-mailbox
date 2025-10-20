<?php

use Bauerdot\FilamentMailBox\Http\Controllers\PixelController;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailOpenEvent;

beforeEach(function () {
    // Ensure required tables exist
    include __DIR__.'/../../database/migrations/create_mail_log_table.php.stub';
    include __DIR__.'/../../database/migrations/create_mail_open_events_table.php.stub';
    include __DIR__.'/../../database/migrations/create_mail_setting_table.php.stub';

    $m1 = include __DIR__.'/../../database/migrations/create_mail_log_table.php.stub';
    $m1->up();
    $m2 = include __DIR__.'/../../database/migrations/create_mail_open_events_table.php.stub';
    $m2->up();
    $m3 = include __DIR__.'/../../database/migrations/create_mail_setting_table.php.stub';
    $m3->up();

    // route used by TrackingPixelListener
    \Illuminate\Support\Facades\Route::get('/filament-mailbox/pixel/{id}', function ($id) {
        return '';
    })->name('filament-mailbox.pixel');
});

it('dedupes quick repeated opens according to dedupe_seconds', function () {
    $log = MailLog::create([
        'from' => 'a@x.com',
        'to' => 'b@x.com',
        'subject' => 's',
        'body' => '',
        'text_body' => '',
        'message_id' => 'dedupe-id',
    ]);

    config()->set('filament-mailbox.tracking.turn_on', true);
    config()->set('filament-mailbox.tracking.level', 'logs');
    config()->set('filament-mailbox.tracking.logs.enabled', true);
    config()->set('filament-mailbox.tracking.logs.dedupe_seconds', 60);

    $controller = new PixelController;

    $req1 = Illuminate\Http\Request::create('/filament-mailbox/pixel/dedupe-id', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
    $req1->headers->set('User-Agent', 'UA1');

    $controller->show($req1, 'dedupe-id');

    // second request same ip/ua within dedupe should not create a new event
    $req2 = Illuminate\Http\Request::create('/filament-mailbox/pixel/dedupe-id', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
    $req2->headers->set('User-Agent', 'UA1');
    $controller->show($req2, 'dedupe-id');

    $count = MailOpenEvent::where('maillog_id', $log->id)->count();
    expect($count)->toBe(1);
});

it('respects store_ip and store_user_agent flags when saving events', function () {
    $log = MailLog::create([
        'from' => 'a@x.com',
        'to' => 'b@x.com',
        'subject' => 's',
        'body' => '',
        'text_body' => '',
        'message_id' => 'store-id',
    ]);

    config()->set('filament-mailbox.tracking.turn_on', true);
    config()->set('filament-mailbox.tracking.level', 'logs');
    config()->set('filament-mailbox.tracking.logs.enabled', true);
    config()->set('filament-mailbox.tracking.logs.store_ip', false);
    config()->set('filament-mailbox.tracking.logs.store_user_agent', false);

    $controller = new PixelController;

    $req = Illuminate\Http\Request::create('/filament-mailbox/pixel/store-id', 'GET', [], [], [], ['REMOTE_ADDR' => '9.9.9.9']);
    $req->headers->set('User-Agent', 'UA2');

    $controller->show($req, 'store-id');

    $evt = MailOpenEvent::where('maillog_id', $log->id)->first();
    expect($evt)->not->toBeNull();
    expect($evt->ip)->toBeNull();
    expect($evt->user_agent)->toBeNull();
});

it('increments opens_count when tracking.level is clicks', function () {
    $log = MailLog::create([
        'from' => 'a@x.com',
        'to' => 'b@x.com',
        'subject' => 's',
        'body' => '',
        'text_body' => '',
        'message_id' => 'clicks-id',
    ]);

    config()->set('filament-mailbox.tracking.turn_on', true);
    config()->set('filament-mailbox.tracking.level', 'clicks');

    $controller = new PixelController;

    $req = Illuminate\Http\Request::create('/filament-mailbox/pixel/clicks-id', 'GET', [], [], [], ['REMOTE_ADDR' => '4.4.4.4']);
    $controller->show($req, 'clicks-id');

    $log->refresh();
    expect($log->opens_count)->toBe(1);
});

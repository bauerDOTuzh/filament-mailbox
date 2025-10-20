<?php

use Bauerdot\FilamentMailBox\Listeners\TrackingPixelListener;
use Bauerdot\FilamentMailBox\Http\Controllers\PixelController;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailOpenEvent;

beforeEach(function () {
    include __DIR__ . '/../../database/migrations/create_mail_log_table.php.stub';
    include __DIR__ . '/../../database/migrations/create_mail_open_events_table.php.stub';
    include __DIR__ . '/../../database/migrations/create_mail_setting_table.php.stub';
    $m = include __DIR__ . '/../../database/migrations/create_mail_log_table.php.stub';
    $m->up();
    $m2 = include __DIR__ . '/../../database/migrations/create_mail_open_events_table.php.stub';
    $m2->up();
    $m3 = include __DIR__ . '/../../database/migrations/create_mail_setting_table.php.stub';
    $m3->up();

    // Define the pixel route used by the TrackingPixelListener
    \Illuminate\Support\Facades\Route::get('/filament-mailbox/pixel/{id}', function ($id) {
        return '';
    })->name('filament-mailbox.pixel');
});

it('inserts tracking pixel into html messages when enabled', function () {
    config()->set('filament-mailbox.tracking.turn_on', true);

    $email = new SymfonyEmail();
    $email->from('from@example.com');
    $email->to('to@example.com');
    $email->html('<html><body>Hello</body></html>');

    $listener = new TrackingPixelListener();
    $listener->handle(new MessageSending($email));

    $html = $email->getHtmlBody();
    expect(str_contains($html, '/filament-mailbox/pixel/'))->toBeTrue();
});

it('pixel controller creates MailOpenEvent when tracking logs enabled', function () {
    // Create a mail log with a message_id
    $log = MailLog::create([
        'from' => 'from@example.com',
        'to' => 'to@example.com',
        'subject' => 's',
        'body' => '',
        'text_body' => '',
        'message_id' => 'test-msg-id',
    ]);

    config()->set('filament-mailbox.tracking.turn_on', true);
    config()->set('filament-mailbox.tracking.level', 'logs');
    config()->set('filament-mailbox.tracking.logs.enabled', true);
    config()->set('filament-mailbox.tracking.logs.store_ip', true);
    config()->set('filament-mailbox.tracking.logs.store_user_agent', true);

    // Simulate a request via the controller
    $request = Illuminate\Http\Request::create('/filament-mailbox/pixel/test-msg-id', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
    $request->headers->set('User-Agent', 'PHPUnit');

    $controller = new PixelController();

    $response = $controller->show($request, 'test-msg-id');

    expect($response->getStatusCode())->toBe(200);
    // MailOpenEvent should be created
    $evt = MailOpenEvent::where('maillog_id', $log->id)->first();
    expect($evt)->not->toBeNull();
    expect($evt->ip)->toBe('1.2.3.4');
});

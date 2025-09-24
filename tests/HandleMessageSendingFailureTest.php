<?php

use Bauerdot\FilamentMailBox\Events\MailLogEventHandler;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email as SymfonyEmail;

it('logs a warning and does not rethrow when handling a failing message', function () {
    // Create a fake message that extends the Symfony Email and throws when any method is accessed
    $failingMessage = new class extends SymfonyEmail
    {
        public function __call($name, $arguments)
        {
            throw new \RuntimeException('simulated message failure');
        }
    };

    // Expect Log::warning to be called
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            // message should contain our simulated message
            return is_string($message) && str_contains($message, 'MailLogEventHandler failed');
        });

    $handler = new MailLogEventHandler;

    // Should not throw
    $handler->handleMessageSending(new MessageSending($failingMessage));

    expect(true)->toBeTrue();
});

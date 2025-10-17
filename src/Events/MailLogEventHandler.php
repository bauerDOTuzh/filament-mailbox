<?php

namespace Bauerdot\FilamentMailBox\Events;

use Bauerdot\FilamentMailBox\Listeners\MailLoggingListener;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

class MailLogEventHandler
{
    /**
     * Legacy event handler method kept for backward compatibility and tests.
     */
    public function handleMessageSending(MessageSending $event): void
    {
        try {
            // Delegate to the new MailLoggingListener which implements the logging logic.
            (new MailLoggingListener())->handle($event);
        } catch (\Throwable $e) {
            Log::warning('MailLogEventHandler failed while handling MessageSending: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}

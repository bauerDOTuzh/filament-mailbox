<?php

namespace Bauerdot\FilamentMailBox\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

class MessageSendingListener
{
    public function handle(MessageSending $event)
    {
        // Delegate to the split listeners for banner & logging behavior to make testing easier.
        try {
            (new EnvironmentBannerListener)->handle($event);
        } catch (\Throwable $e) {
            // don't break mail sending if banner fails
            Log::warning('EnvironmentBannerListener failed: '.$e->getMessage(), ['exception' => $e]);
        }

        try {
            (new MailLoggingListener)->handle($event);
        } catch (\Throwable $e) {
            // Log and swallow, again don't break mail sending if logging fails
            Log::warning('MailLoggingListener failed: '.$e->getMessage(), ['exception' => $e]);
        }

        try {
            // Only add tracking pixel if enabled in config (default true)
            if (config('filament-mailbox.tracking.turn_on', true)) {
                (new TrackingPixelListener)->handle($event);
            }
        } catch (\Throwable $e) {
            Log::warning('TrackingPixelListener failed: '.$e->getMessage(), ['exception' => $e]);
        }

    }
}

<?php

namespace Bauerdot\FilamentMailBox\Listeners;

use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Illuminate\Mail\Events\MessageSending;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Bauerdot\FilamentMailBox\Listeners\MailLoggingListener;
use Bauerdot\FilamentMailBox\Listeners\TrackingPixelListener;
use Bauerdot\FilamentMailBox\Listeners\EnvironmentBannerListener;

class MessageSendingListener
{
    public function handle(MessageSending $event)
    {
        // Delegate to the split listeners for banner & logging behavior to make testing easier.
        try {
            (new EnvironmentBannerListener())->handle($event);
        } catch (\Throwable $e) {
            // don't break mail sending if banner fails
            Log::warning('EnvironmentBannerListener failed: '.$e->getMessage(), ['exception' => $e]);
        }

        try {
            (new MailLoggingListener())->handle($event);
        } catch (\Throwable $e) {
            // Log and swallow, again don't break mail sending if logging fails
            Log::warning('MailLoggingListener failed: '.$e->getMessage(), ['exception' => $e]);
        }

        try {
            // Only add tracking pixel if enabled in config (default true)
            if (config('filament-mailbox.tracking.turn_on', true)) {
                (new TrackingPixelListener())->handle($event);
            }
        } catch (\Throwable $e) {
            Log::warning('TrackingPixelListener failed: '.$e->getMessage(), ['exception' => $e]);
        }


    }
}

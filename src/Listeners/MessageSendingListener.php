<?php

namespace Bauerdot\FilamentMailBox\Listeners;

use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

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
    }
}

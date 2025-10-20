<?php

namespace Bauerdot\FilamentMailBox\Listeners;

use Bauerdot\FilamentMailBox\Models\MailLog;
use Illuminate\Mail\Events\MessageSent;

class MailSentListener
{
    public function handle(MessageSent $event): void
    {
        $headers = $event->message->getHeaders();
        $unique = $headers->get('unique-id');
        $messageId = $unique ? $unique->getBodyAsString() : null;

        if (! $messageId) {
            return;
        }
        // $providerMessageId = method_exists($event, 'sent') && $event->sent
        //     ? $event->sent->getMessageId()
        //     : (optional($headers->get('Message-Id'))->getBodyAsString());

        $maillog = MailLog::where('message_id', $messageId)->first();
        if ($maillog) {
            $maillog->markSent();
        }
    }
}

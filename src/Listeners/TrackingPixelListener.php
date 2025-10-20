<?php

namespace Bauerdot\FilamentMailBox\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class TrackingPixelListener
{
    public function handle(MessageSending $event): void
    {
        $message = $event->message;

        // Reuse the message_id that MailLoggingListener stored in the header.
        // If, for any reason, it's missing, mint one so the pixel still works.
        $headers = $message->getHeaders();
        $header = $headers->get('unique-id');
        $messageId = $header ? $header->getBodyAsString() : (string) Str::ulid();

        if (! $header) {
            $headers->addTextHeader('unique-id', $messageId);
        }

        // Only touch HTML emails; leave text-only untouched (simple path)
        $html = $message instanceof Email ? $message->getHtmlBody() : null;
        if (! $html) {
            return;
        }

        // Avoid inserting twice if a resend/clone happens
        if (str_contains($html, '/filament-mailbox/pixel/')) {
            return;
        }

        $pixelUrl = route('filament-mailbox.pixel', ['id' => $messageId]);

        $img = '<img src="'.$pixelUrl.'" width="1" height="1" alt="" style="display:block;border:0;outline:0;line-height:0;">';

        // Try to tuck it before </body>; otherwise append at the end
        if (preg_match('/<\/body\s*>/i', $html)) {
            $html = preg_replace('/<\/body\s*>/i', $img.'</body>', $html, 1);
        } else {
            $html .= $img;
        }

        // Replace the HTML part
        $message->html($html);
    }
}

<?php

declare(strict_types=1);

namespace Bauerdot\FilamentMailLog\Events;

use Bauerdot\FilamentMailLog\Models\MailLog;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

class MailLogEventHandler
{
    public function __construct()
    {
        //
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            MessageSending::class,
            MailLogEventHandler::class.'@handleMessageSending',
        );
    }

    /**
     * Handle the event.
     */
    public function handleMessageSending(MessageSending $event): void
    {
        try {
            $message = $event->message;

            $mailLog = MailLog::create([
                'from' => $this->getAddressesValue($message->getFrom()),
                'to' => $this->getAddressesValue($message->getTo()),
                'cc' => $this->getAddressesValue($message->getCc()),
                'bcc' => $this->getAddressesValue($message->getBcc()),
                'subject' => $message->getSubject(),
                'body' => $message->getHtmlBody() ?? '',
                'text_body' => $message->getTextBody() ?? '',
                'headers' => $message->getHeaders()->toString(),
                'attachments' => $this->saveAttachments($message),
                'message_id' => (string) Str::uuid(),
            ]);

            if (config('filament-maillog.amazon-ses.configuration-set') !== null) {
                $event->message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', config('filament-maillog.amazon-ses.configuration-set'));
            }

            $event->message->getHeaders()->addTextHeader('unique-id', $mailLog->message_id);
        } catch (Throwable $e) {
            // Log a warning and swallow the exception so mail sending continues.
            Log::warning('MailLogEventHandler failed while handling MessageSending: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Format address strings for sender, to, cc, bcc.
     */
    protected function getAddressesValue(array $address): ?Collection
    {
        $addresses = collect($address)
            ->flatMap(fn (Address $address) => [$address->getAddress() => $address->getName() === '' ? null : $address->getName()]);

        return $addresses->count() > 0 ? $addresses : null;
    }

    /**
     * Collect all attachments and format them as strings.
     */
    protected function saveAttachments(Email $message): ?string
    {
        if (empty($message->getAttachments())) {
            return null;
        }

        return collect($message->getAttachments())
            ->map(fn (DataPart $part) => $part->toString())
            ->implode("\n\n");
    }
}

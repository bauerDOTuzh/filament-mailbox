<?php

namespace Bauerdot\FilamentMailBox\Listeners;

use Bauerdot\FilamentMailBox\Enums\MailStatus;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class MailLoggingListener
{
    public function handle(MessageSending $event)
    {
        $message = $event->message;

        // Apply global BCC settings
        $settings = MailSettingsDto::fromConfigAndModel();
        $this->applyGlobalBcc($message, $settings);

        // Apply sandbox redirection for non-production environments
        $environment = app()->environment();
        if ($environment !== 'production' && $settings->sandbox_mode) {
            $this->applySandboxRedirection($message, $environment, $settings);
        }

        try {
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
                'message_id' => (string) Str::ulid(),
            ]);

            // Ensure initial status is set to UNSENT without allowing mass-assignment
            $mailLog->status = MailStatus::UNSENT;
            $mailLog->save();

            if (config('filament-mailbox.amazon-ses.configuration-set') !== null) {
                $event->message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', config('filament-mailbox.amazon-ses.configuration-set'));
            }

            $event->message->getHeaders()->addTextHeader('unique-id', $mailLog->message_id);
        } catch (\Throwable $e) {
            Log::warning('MailLogEventHandler failed while handling MessageSending: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    private function applyGlobalBcc($message, MailSettingsDto $settings): void
    {
        $bccAddresses = $settings->bcc_address ?? [];

        // Ensure it's a simple array of emails
        if (! is_array($bccAddresses)) {
            $bccAddresses = [];
        }

        $message->bcc(...$bccAddresses);
    }

    private function applySandboxRedirection($message, string $environment, MailSettingsDto $settings): void
    {
        $allowedEmails = $settings->allowed_emails ?? [];
        $sandboxAddress = $this->normalizeEmail($settings->sandbox_address ?? null);

        $originalTo = $message->getTo() ?: [];
        $originalCc = $message->getCc() ?: [];
        $originalBcc = $message->getBcc() ?: [];

        if (! empty($originalTo)) {
            $addresses = array_map(fn ($address) => $address->getAddress(), $originalTo);
            $message->getHeaders()->addTextHeader('X-Original-To', implode(', ', $addresses));
        }

        $filteredTo = $this->filterRecipients($originalTo, $allowedEmails);
        $filteredCc = $this->filterRecipients($originalCc, $allowedEmails);
        $filteredBcc = $this->filterRecipients($originalBcc, $allowedEmails);

        $hasBlockedRecipients = count($filteredTo) < count($originalTo) ||
                       count($filteredCc) < count($originalCc) ||
                       count($filteredBcc) < count($originalBcc);

        if ($hasBlockedRecipients && $sandboxAddress !== null) {
            $filteredTo[] = $sandboxAddress;
            $filteredTo = array_unique($filteredTo);
        }

        if (! empty($filteredTo)) {
            $message->to(...$filteredTo);
        }
        if (! empty($filteredCc)) {
            $message->cc(...$filteredCc);
        }
        if (! empty($filteredBcc)) {
            $message->bcc(...$filteredBcc);
        }

        if ($hasBlockedRecipients) {
            $originalSubject = $message->getSubject();
            $message->subject('[TEST - '.config('app.name', 'Laravel').' - '.$environment.'] '.$originalSubject);
        }
    }

    private function filterRecipients(array $recipients, array $allowedEmails): array
    {
        $filtered = [];

        foreach ($recipients as $recipient) {
            $email = $recipient->getAddress();
            if (is_array($allowedEmails) && in_array($email, $allowedEmails, true)) {
                $filtered[] = $email;
            }
        }

        return $filtered;
    }

    private function normalizeEmail($email): ?string
    {
        if ($email === null) {
            return null;
        }

        if (is_array($email)) {
            $email = array_values(array_filter($email, fn ($v) => trim((string) $v) !== ''))[0] ?? null;
        }

        $email = trim((string) ($email ?? ''));

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function getAddressesValue(array $address): ?string
    {
        $addresses = collect($address)->map(fn ($address) => $address->toString());

        return $addresses->count() > 0 ? $addresses->implode(', ') : null;

    }

    protected function saveAttachments(Email $message): ?string
    {
        if (empty($message->getAttachments())) {
            return null;
        }

        return collect($message->getAttachments())
            ->map(fn (DataPart $part) => $part->toString())
            ->implode("\n\n");
    }

    /**
     * Determine if the current mail transport/mailer should be auto-marked as delivered.
     *
     * @param  array  $allowedTransports
     * @return bool
     */
    // autodeliver handling removed — capability is no longer tracked here
}

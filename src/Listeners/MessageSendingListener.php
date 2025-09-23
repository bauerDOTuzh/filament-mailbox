<?php

namespace Bauerdot\FilamentMailLog\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Bauerdot\FilamentMailLog\Models\MailSetting;

class MessageSendingListener
{
    public function handle(MessageSending $event)
    {
        $message = $event->message;
        $environment = app()->environment();

        // Apply global BCC settings first
        $this->applyGlobalBcc($message);

        // Always add warning banner to email body if configured
        if (MailSetting::getValue('show_environment_banner', config('mail.show_environment_banner'))) {
            $originalTo = $message->getTo();
            $originalCc = $message->getCc();
            $originalBcc = $message->getBcc();
            $this->addEnvironmentBanner($message, $environment, $originalTo, $originalCc, $originalBcc);
        }

        if ($environment !== 'production') {
            if (MailSetting::getValue('sandbox_mode', config('mail.sandbox_mode'))) {
                $this->applySandboxRedirection($message, $environment);
            }
        }
    }

    protected function addEnvironmentBanner($message, string $environment, ?array $originalTo = null, ?array $originalCc = null, ?array $originalBcc = null): void
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'unknown-domain';
        $appName = config('app.name', 'Laravel');

        $hasRedirectedRecipients = $originalTo !== null;

        $recipients = $hasRedirectedRecipients
            ? $this->formatOriginalRecipients($originalTo, $originalCc, $originalBcc)
            : $this->getRecipientsInfo($message);

        $redirectedTo = $hasRedirectedRecipients ? MailSetting::getValue('sandbox_address', config('mail.sandbox_address')) : null;
        $timestamp = date('Y-m-d H:i:s');

        // Use a simple inline banner if view not available
        try {
            $banner = view('filament-maillog::banner', compact('environment', 'appName', 'domain', 'hasRedirectedRecipients', 'recipients', 'redirectedTo', 'timestamp'))->render();
        } catch (\Throwable $e) {
            $banner = "<div style='padding:10px;border:2px solid #f00;background:#fff3f3;color:#900;font-family:Arial;'>[{$environment}] {$appName} - Mail Sandbox<br/>Recipients: {$recipients}<br/>Redirected To: {$redirectedTo}</div><br/>";
        }

        $body = method_exists($message, 'getHtmlBody') ? $message->getHtmlBody() : null;

        if ($body) {
            $message->html($banner . $body);
        } else {
            $textBody = method_exists($message, 'getTextBody') ? $message->getTextBody() : null;
            if ($textBody) {
                $message->html($banner . '<pre>' . htmlspecialchars($textBody) . '</pre>');
                $message->text($textBody);
            }
        }
    }

    private function getRecipientsInfo($message): string
    {
        $recipients = [];

        $to = $message->getTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        if (!empty($to)) {
            $toAddresses = array_map(fn($address) => $address->getAddress(), $to);
            $recipients[] = '<span style="text-decoration: underline">To</span>: ' . implode(', ', $toAddresses);
        }

        if (!empty($cc)) {
            $ccAddresses = array_map(fn($address) => $address->getAddress(), $cc);
            $recipients[] = '<span style="text-decoration: underline">CC</span>: ' . implode(', ', $ccAddresses);
        }

        if (!empty($bcc)) {
            $bccAddresses = array_map(fn($address) => $address->getAddress(), $bcc);
            $recipients[] = '<span style="text-decoration: underline">BCC</span>: ' . implode(', ', $bccAddresses);
        }

        return !empty($recipients) ? implode(' | ', $recipients) : 'No recipients found';
    }

    private function formatOriginalRecipients(?array $to = null, ?array $cc = null, ?array $bcc = null): string
    {
        $recipients = [];

        if (!empty($to)) {
            $toAddresses = array_map(fn($address) => $address->getAddress(), $to);
            $recipients[] = '<span style="text-decoration: underline">To</span>: ' . implode(', ', $toAddresses);
        }

        if (!empty($cc)) {
            $ccAddresses = array_map(fn($address) => $address->getAddress(), $cc);
            $recipients[] = '<span style="text-decoration: underline">CC</span>: ' . implode(', ', $ccAddresses);
        }

        if (!empty($bcc)) {
            $bccAddresses = array_map(fn($address) => $address->getAddress(), $bcc);
            $recipients[] = '<span style="text-decoration: underline">BCC</span>: ' . implode(', ', $bccAddresses);
        }

        return !empty($recipients) ? implode(' | ', $recipients) : 'No recipients found';
    }

    private function applyGlobalBcc($message): void
    {
        $bccAddresses = MailSetting::getValue('bcc_address', config('mail.bcc_address', []));

        if (!empty($bccAddresses)) {
            foreach ($bccAddresses as $bccAddress) {
                if (filter_var(trim($bccAddress), FILTER_VALIDATE_EMAIL)) {
                    $message->bcc(trim($bccAddress));
                }
            }
        }
    }

    private function applySandboxRedirection($message, string $environment): void
    {
        $allowedEmails = MailSetting::getValue('allowed_emails', config('mail.allowed_emails', []));
        $sandboxAddress = MailSetting::getValue('sandbox_address', config('mail.sandbox_address'));

        $originalTo = $message->getTo() ?: [];
        $originalCc = $message->getCc() ?: [];
        $originalBcc = $message->getBcc() ?: [];

        if (!empty($originalTo)) {
            $addresses = array_map(fn($address) => $address->getAddress(), $originalTo);
            $message->getHeaders()->addTextHeader('X-Original-To', implode(', ', $addresses));
        }

        $filteredTo = $this->filterRecipients($originalTo, $allowedEmails);
        $filteredCc = $this->filterRecipients($originalCc, $allowedEmails);
        $filteredBcc = $this->filterRecipients($originalBcc, $allowedEmails);

        $hasBlockedRecipients = count($filteredTo) < count($originalTo) ||
                               count($filteredCc) < count($originalCc) ||
                               count($filteredBcc) < count($originalBcc);

        if ($hasBlockedRecipients) {
            $filteredTo[] = $sandboxAddress;
            $filteredTo = array_unique($filteredTo);
        }

        if (!empty($filteredTo)) {
            $message->to(...$filteredTo);
        }
        if (!empty($filteredCc)) {
            $message->cc(...$filteredCc);
        }
        if (!empty($filteredBcc)) {
            $message->bcc(...$filteredBcc);
        }

        if ($hasBlockedRecipients) {
            $originalSubject = $message->getSubject();
            $message->subject("[TEST - " . config('app.name', 'Laravel') . " - " . $environment . "] " . $originalSubject);
        }
    }

    private function filterRecipients(array $recipients, array $allowedEmails): array
    {
        $filtered = [];

        foreach ($recipients as $recipient) {
            $email = $recipient->getAddress();
            if (in_array($email, $allowedEmails)) {
                $filtered[] = $email;
            }
        }

        return $filtered;
    }
}

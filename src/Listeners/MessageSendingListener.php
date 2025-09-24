<?php

namespace Bauerdot\FilamentMailLog\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Bauerdot\FilamentMailLog\Models\MailSetting;
use Bauerdot\FilamentMailLog\Models\MailSettingsDto;

class MessageSendingListener
{
    public function handle(MessageSending $event)
    {
        $message = $event->message;
        $environment = app()->environment();
        // Load settings DTO (config defaults merged with DB + cached)
        $settings = MailSettingsDto::fromConfigAndModel();

        // Apply global BCC settings first
        $this->applyGlobalBcc($message, $settings);

        // Always add warning banner to email body if configured
        if ($settings->show_environment_banner) {
            $originalTo = $message->getTo();
            $originalCc = $message->getCc();
            $originalBcc = $message->getBcc();
            $this->addEnvironmentBanner($message, $environment, $settings, $originalTo, $originalCc, $originalBcc);
        }

        if ($environment !== 'production') {
            if ($settings->sandbox_mode) {
                $this->applySandboxRedirection($message, $environment, $settings);
            }
        }
    }

    protected function addEnvironmentBanner($message, string $environment, MailSettingsDto $settings, ?array $originalTo = null, ?array $originalCc = null, ?array $originalBcc = null): void
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'unknown-domain';
        $appName = config('app.name', 'Laravel');

        $hasRedirectedRecipients = $originalTo !== null;

        $recipients = $hasRedirectedRecipients
            ? $this->formatOriginalRecipients($originalTo, $originalCc, $originalBcc)
            : $this->getRecipientsInfo($message);

    $redirectedToRaw = $hasRedirectedRecipients ? $settings->sandbox_address : null;
    $redirectedTo = $this->normalizeEmail($redirectedToRaw) ?? null;
        $timestamp = date('Y-m-d H:i:s');

        // Use a simple inline banner if view not available
        try {
            // Ensure redirectedTo is a string for the banner view
            $redirectedToForView = $redirectedTo ?? 'None';
            $banner = view('filament-maillog::banner', compact('environment', 'appName', 'domain', 'hasRedirectedRecipients', 'recipients', 'redirectedToForView', 'timestamp'))->render();
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

    private function applyGlobalBcc($message, MailSettingsDto $settings): void
    {
        $bccAddresses = $settings->bcc_address ?? [];

        // Normalize to array and filter invalid/empty addresses
        if (!is_array($bccAddresses)) {
            if (is_string($bccAddresses) && trim($bccAddresses) !== '') {
                $bccAddresses = [$bccAddresses];
            } else {
                $bccAddresses = [];
            }
        }

        foreach ($bccAddresses as $bccAddress) {
            $email = $this->normalizeEmail($bccAddress);
            if ($email !== null) {
                $message->bcc($email);
            }
        }
    }

    private function applySandboxRedirection($message, string $environment, MailSettingsDto $settings): void
    {
        $allowedEmails = is_array($settings->allowed_emails) ? $settings->allowed_emails : [];
        $sandboxAddress = $this->normalizeEmail($settings->sandbox_address);

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

        if ($hasBlockedRecipients && $sandboxAddress !== null) {
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
            // only include recipient if it's present in allowedEmails
            if (is_array($allowedEmails) && in_array($email, $allowedEmails, true)) {
                $filtered[] = $email;
            }
        }

        return $filtered;
    }

    /**
     * Normalize an email value: trim and validate. Returns null when invalid/empty.
     */
    private function normalizeEmail($email): ?string
    {
        if ($email === null) {
            return null;
        }

        // If stored as array (unexpected), pick first non-empty value
        if (is_array($email)) {
            $email = array_values(array_filter($email, fn($v) => trim((string) $v) !== ''))[0] ?? null;
        }

        $email = trim((string) ($email ?? ''));

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}

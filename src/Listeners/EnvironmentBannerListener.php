<?php

namespace Bauerdot\FilamentMailBox\Listeners;

use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;

class EnvironmentBannerListener
{
    public function handle(MessageSending $event)
    {
        $message = $event->message;
        $environment = app()->environment();
        $settings = MailSettingsDto::fromConfigAndModel();

        if (! $settings->show_environment_banner) {
            return;
        }

        $originalTo = $message->getTo();
        $originalCc = $message->getCc();
        $originalBcc = $message->getBcc();

        $this->addEnvironmentBanner($message, $environment, $settings, $originalTo, $originalCc, $originalBcc);
    }

    // Copy of existing addEnvironmentBanner and helpers but scoped to banner only
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

        try {
            $redirectedToForView = $redirectedTo ?? 'None';
            $banner = view('filament-mailbox::banner', compact('environment', 'appName', 'domain', 'hasRedirectedRecipients', 'recipients', 'redirectedToForView', 'timestamp'))->render();
        } catch (\Throwable $e) {
            $banner = "<div style='padding:10px;border:2px solid #f00;background:#fff3f3;color:#900;font-family:Arial;'>[{$environment}] {$appName} - Mail Sandbox<br/>Recipients: {$recipients}<br/>Redirected To: {$redirectedTo}</div><br/>";
        }

        $body = method_exists($message, 'getHtmlBody') ? $message->getHtmlBody() : null;

        if ($body) {
            $message->html($banner.$body);
        } else {
            $textBody = method_exists($message, 'getTextBody') ? $message->getTextBody() : null;
            if ($textBody) {
                $message->html($banner.'<pre>'.htmlspecialchars($textBody).'</pre>');
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

        if (! empty($to)) {
            $toAddresses = array_map(fn ($address) => $address->getAddress(), $to);
            $recipients[] = '<span style="text-decoration: underline">To</span>: '.implode(', ', $toAddresses);
        }

        if (! empty($cc)) {
            $ccAddresses = array_map(fn ($address) => $address->getAddress(), $cc);
            $recipients[] = '<span style="text-decoration: underline">CC</span>: '.implode(', ', $ccAddresses);
        }

        if (! empty($bcc)) {
            $bccAddresses = array_map(fn ($address) => $address->getAddress(), $bcc);
            $recipients[] = '<span style="text-decoration: underline">BCC</span>: '.implode(', ', $bccAddresses);
        }

        return ! empty($recipients) ? implode(' | ', $recipients) : 'No recipients found';
    }

    private function formatOriginalRecipients(?array $to = null, ?array $cc = null, ?array $bcc = null): string
    {
        $recipients = [];

        if (! empty($to)) {
            $toAddresses = array_map(fn ($address) => $address->getAddress(), $to);
            $recipients[] = '<span style="text-decoration: underline">To</span>: '.implode(', ', $toAddresses);
        }

        if (! empty($cc)) {
            $ccAddresses = array_map(fn ($address) => $address->getAddress(), $cc);
            $recipients[] = '<span style="text-decoration: underline">CC</span>: '.implode(', ', $ccAddresses);
        }

        if (! empty($bcc)) {
            $bccAddresses = array_map(fn ($address) => $address->getAddress(), $bcc);
            $recipients[] = '<span style="text-decoration: underline">BCC</span>: '.implode(', ', $bccAddresses);
        }

        return ! empty($recipients) ? implode(' | ', $recipients) : 'No recipients found';
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
}

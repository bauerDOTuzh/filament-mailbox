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
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: __('filament-mailbox::filament-mailbox.banner.unknown_domain');
        $appName = config('app.name', 'Laravel');

        $hasRedirectedRecipients = $originalTo !== null;

        $recipients = $hasRedirectedRecipients
            ? $this->formatOriginalRecipients($originalTo, $originalCc, $originalBcc)
            : $this->getRecipientsInfo($message);

        // Render banner using Blade view from the package views. The view expects
        // Determine redirected-t   o address when sandbox redirection is enabled
        $redirectedTo = null;
        if ($environment !== 'production' && $settings->sandbox_mode) {
            $redirectedTo = $this->normalizeEmail($settings->sandbox_address ?? null);
        }

        $viewData = [
            'appName' => $appName,
            'environment' => $environment,
            'domain' => $domain,
            'hasRedirectedRecipients' => $hasRedirectedRecipients,
            'recipients' => $recipients,
            'redirectedTo' => $redirectedTo,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Use the package view namespace registered by Spatie's package tools
        $banner = null;
        try {
            $banner = view('filament-mailbox::banner', $viewData)->render();
        } catch (\Throwable $e) {
            // If rendering fails (e.g. view namespace not registered in tests),
            // we'll fall back to a minimal inline banner so the behavior is
            // consistent even without the published view files.
            $banner = '<div style="background:#f5f5f5;padding:8px;border-bottom:1px solid #ddd;font-family:Arial,Helvetica,sans-serif;font-size:12px;">'
                .htmlspecialchars(sprintf('%s environment - %s', $appName, $environment))
                .'</div>';
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
            $recipients[] = '<span style="text-decoration: underline">'.__('filament-mailbox::filament-mailbox.to').'</span>: '.implode(', ', $toAddresses);
        }

        if (! empty($cc)) {
            $ccAddresses = array_map(fn ($address) => $address->getAddress(), $cc);
            $recipients[] = '<span style="text-decoration: underline">'.__('filament-mailbox::filament-mailbox.cc').'</span>: '.implode(', ', $ccAddresses);
        }

        if (! empty($bcc)) {
            $bccAddresses = array_map(fn ($address) => $address->getAddress(), $bcc);
            $recipients[] = '<span style="text-decoration: underline">'.__('filament-mailbox::filament-mailbox.bcc').'</span>: '.implode(', ', $bccAddresses);
        }

        return ! empty($recipients) ? implode(' | ', $recipients) : __('filament-mailbox::filament-mailbox.banner.no_recipients');
    }

    private function formatOriginalRecipients(?array $to = null, ?array $cc = null, ?array $bcc = null): string
    {
        $recipients = [];

        if (! empty($to)) {
            $toAddresses = array_map(fn ($address) => $address->getAddress(), $to);
            $recipients[] = '<span style="text-decoration: underline">'.__('filament-mailbox::filament-mailbox.to').'</span>: '.implode(', ', $toAddresses);
        }

        if (! empty($cc)) {
            $ccAddresses = array_map(fn ($address) => $address->getAddress(), $cc);
            $recipients[] = '<span style="text-decoration: underline">'.__('filament-mailbox::filament-mailbox.cc').'</span>: '.implode(', ', $ccAddresses);
        }

        if (! empty($bcc)) {
            $bccAddresses = array_map(fn ($address) => $address->getAddress(), $bcc);
            $recipients[] = '<span style="text-decoration: underline">'.__('filament-mailbox::filament-mailbox.bcc').'</span>: '.implode(', ', $bccAddresses);
        }

        return ! empty($recipients) ? implode(' | ', $recipients) : __('filament-mailbox::filament-mailbox.banner.no_recipients');
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

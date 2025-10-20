<?php

namespace Bauerdot\FilamentMailBox\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Bauerdot\FilamentMailBox\Models\MailSetting;

/**
 * Simple DTO for typed mail settings access.
 */
final class MailSettingsDto
{
    public bool $show_environment_banner;

    public bool $sandbox_mode;

    public ?string $sandbox_address;

    public array $bcc_address;

    public array $allowed_emails;

    public bool $tracking_on;

    public string $tracking_level;

    /**
     * Whether the current mailer supports delivery statistics (open/click/etc).
     */
    public bool $supports_stats = true;

    public function __construct(array $data)
    {
        $this->show_environment_banner = (bool) ($data['show_environment_banner'] ?? true);
        $this->sandbox_mode = (bool) ($data['sandbox_mode'] ?? false);
        $this->sandbox_address = $data['sandbox_address'] ?? null;
        $this->bcc_address = $this->normalizeEmailList($data['bcc_address'] ?? []);
        $this->allowed_emails = $this->normalizeEmailList($data['allowed_emails'] ?? []);
        $this->supports_stats = (bool) ($data['supports_stats'] ?? true);
        $this->tracking_on = config('filament-mailbox.tracking.turn_on', true);
        $this->tracking_level = config('filament-mailbox.tracking.level', true);
    }

    private function normalizeEmailList($value): array
    {
        if (is_array($value)) {
            $list = $value;
        } elseif (is_string($value)) {
            $list = array_map('trim', explode(',', $value));
        } else {
            $list = [];
        }

        // Filter out empty values and validate emails
        $list = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $list), fn ($v) => $v !== ''));

        $valid = [];
        foreach ($list as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $email;
            }
        }

        return $valid;
    }

    public static function cacheKey(): string
    {
        return 'filament-mailbox.mail_settings.dto';
    }

    public static function fromConfigAndModel(bool $useCache = true): self
    {
        $cacheKey = self::cacheKey();
        $ttl = Config::get('filament-mailbox.mail_settings.cache_ttl', null);

        // We cache the merged settings array instead of the DTO instance. Caching the
        // raw array avoids issues with serializing objects that have uninitialized
        // typed properties. Rebuild the DTO from the array on each read.
        if ($useCache && Cache::has($cacheKey)) {
            $merged = Cache::get($cacheKey, []);
        } else {
            $defaults = Config::get('filament-mailbox.mail_settings.defaults', []);

            // Pull db values (MailSetting::allCached will merge defaults and populate per-key cache)
            $rows = MailSetting::allCached();

            $merged = array_merge($defaults, $rows);

            Cache::put($cacheKey, $merged, $ttl);
        }

        $dto = new self($merged);

        // Determine mailer/driver and derive capability flags. We prefer the framework config
        // `mail.default` (Laravel uses MAIL_MAILER env)
        $mailer = Config::get('mail.default', null);
        $driver = is_string($mailer) ? strtolower($mailer) : null;

        // For certain drivers (e.g. smtp or log) we do not have meaningful delivery stats
        $dto->supports_stats = ! in_array($driver, ['smtp', 'log'], true);

        return $dto;
    }

    public static function flushCache(): void
    {
        Cache::forget(self::cacheKey());
    }
}

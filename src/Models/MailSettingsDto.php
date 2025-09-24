<?php

namespace Bauerdot\FilamentMailBox\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

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

    public function __construct(array $data)
    {
        $this->show_environment_banner = (bool) ($data['show_environment_banner'] ?? true);
        $this->sandbox_mode = (bool) ($data['sandbox_mode'] ?? false);
        $this->sandbox_address = $data['sandbox_address'] ?? null;
        $this->bcc_address = $this->normalizeEmailList($data['bcc_address'] ?? []);
        $this->allowed_emails = $this->normalizeEmailList($data['allowed_emails'] ?? []);
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

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $defaults = Config::get('filament-mailbox.mail_settings.defaults', []);

        // Pull db values (MailSetting::allCached will merge defaults)
        $rows = MailSetting::allCached();

        $merged = array_merge($defaults, $rows);

        $dto = new self($merged);

        Cache::put($cacheKey, $dto, $ttl);

        return $dto;
    }

    public static function flushCache(): void
    {
        Cache::forget(self::cacheKey());
    }
}

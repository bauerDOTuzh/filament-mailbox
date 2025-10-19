<?php

return [
    'amazon-ses' => [
        'configuration-set' => null,
    ],

    'resources' => [
        'MailLogResource' => \Bauerdot\FilamentMailBox\Resources\MailLogResource::class,
        'MailSettingResource' => \Bauerdot\FilamentMailBox\Resources\MailSettingResource::class,
    ],

    'navigation' => [
        'maillog' => [
            'register' => true,
            'sort' => 1,
            'icon' => 'heroicon-o-rectangle-stack',
        ],
        'settings' => [
            'register' => true,
            'icon' => 'heroicon-o-cog',
            'sort' => 2,
        ],
    ],

    'sort' => [
        'column' => 'created_at',
        'direction' => 'desc',
    ],

    // Default mail settings and lock behaviour
    'mail_settings' => [
        // Default values for settings when not present in DB
        'defaults' => [
            // Allow environment overrides for these settings. Multiple emails may be provided
            // as comma-separated values in the env (e.g. MAIL_LOG_BCC_ADDRESS=one@example.com,two@example.com)
            'show_environment_banner' => env('MAIL_LOG_SHOW_ENV_BANNER', true),
            'sandbox_mode' => env('MAIL_LOG_SANDBOX_MODE', false),
            'sandbox_address' => env('MAIL_LOG_SANDBOX_ADDRESS', null),
            'bcc_address' => (function () {
                $v = env('MAIL_LOG_BCC_ADDRESS', null);
                if (is_null($v) || $v === '') {
                    return [];
                }
                if (is_array($v)) {
                    return $v;
                }

                return array_values(array_filter(array_map('trim', explode(',', (string) $v))));
            })(),
            'allowed_emails' => (function () {
                $v = env('MAIL_LOG_ALLOWED_EMAILS', null);
                if (is_null($v) || $v === '') {
                    return [];
                }
                if (is_array($v)) {
                    return $v;
                }

                return array_values(array_filter(array_map('trim', explode(',', (string) $v))));
            })(),
        ],

        // When true, values defined in 'defaults' will be considered locked
        // and cannot be changed via the UI or programmatically through
        // MailSetting::setValue()
        'lock_values' => false,
        // Cache TTL for mail settings (seconds). Null = forever.
        'cache_ttl' => null,
    ],

    // Tracking configuration (controls adding tracking pixels)
    'tracking' => [
        // When true, tracking pixels will be added to outgoing HTML emails.
        // Can be set via environment variable MAIL_LOG_TRACKING_TURN_ON (true/false).
        'turn_on' => env('MAIL_LOG_TRACKING_TURN_ON', true),
    ],

];

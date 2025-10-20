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
                if (is_string($v)) {
                    return array_values(array_filter(array_map('trim', explode(',', $v))));
                }

                return [];
            })(),
            'allowed_emails' => (function () {
                $v = env('MAIL_LOG_ALLOWED_EMAILS', null);
                if (is_null($v) || $v === '') {
                    return [];
                }
                if (is_string($v)) {
                    return array_values(array_filter(array_map('trim', explode(',', $v))));
                }

                return [];
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
        // Global enable/disable for tracking pixels
        'turn_on' => env('MAIL_LOG_TRACKING_TURN_ON', true),

        // Level decides where granular tracking is stored:
        // - 'clicks' => increments counters on mail_logs (opens_count)
        // - 'logs' => stores per-open/per-click rows in a separate table (mail_open_events)
        // Default is 'logs' (more detailed)
        'level' => env('MAIL_LOG_TRACKING_LEVEL', 'logs'),

        // Options for 'logs' level: granular open event storage
        'logs' => [
            'enabled' => env('MAIL_LOG_TRACKING_LOGS_ENABLED', true),
            'store_ip' => env('MAIL_LOG_TRACKING_LOGS_STORE_IP', true),
            'store_user_agent' => env('MAIL_LOG_TRACKING_LOGS_STORE_UA', true),
            'store_headers' => env('MAIL_LOG_TRACKING_LOGS_STORE_HEADERS', false),
            'dedupe_seconds' => (int) env('MAIL_LOG_TRACKING_LOGS_DEDUPE_SECONDS', 60),
            'retention_days' => (int) env('MAIL_LOG_TRACKING_LOGS_RETENTION_DAYS', 180),
        ],
    ],

];

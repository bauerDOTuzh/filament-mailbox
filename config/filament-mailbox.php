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
            // as comma-separated values in the env (e.g. MAILBOX_BCC_ADDRESS=one@example.com,two@example.com)
            'show_environment_banner' => env('MAILBOX_SHOW_ENV_BANNER', true),
            'sandbox_mode' => env('MAILBOX_SANDBOX_MODE', false),
            'sandbox_address' => env('MAILBOX_SANDBOX_ADDRESS', null),
            'bcc_address' => (function () {
                $v = env('MAILBOX_BCC_ADDRESS', null);
                if (is_null($v) || $v === '') {
                    return [];
                }
                if (is_string($v)) {
                    return array_values(array_filter(array_map('trim', explode(',', $v))));
                }

                return [];
            })(),
            'allowed_emails' => (function () {
                $v = env('MAILBOX_ALLOWED_EMAILS', null);
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
        'turn_on' => env('MAILBOX_TRACKING_TURN_ON', true),

        // Level decides where granular tracking is stored:
        // - 'clicks' => increments counters on mail_logs (opens_count)
        // - 'logs' => stores per-open/per-click rows in a separate table (mail_open_events)
        // Default is 'logs' (more detailed)
        'level' => env('MAILBOX_TRACKING_LEVEL', 'logs'),

        // Options for 'logs' level: granular open event storage
        'logs' => [
            'enabled' => env('MAILBOX_TRACKING_LOGS_ENABLED', true),
            'store_ip' => env('MAILBOX_TRACKING_LOGS_STORE_IP', true),
            'store_user_agent' => env('MAILBOX_TRACKING_LOGS_STORE_UA', true),
            'store_headers' => env('MAILBOX_TRACKING_LOGS_STORE_HEADERS', false),
            'dedupe_seconds' => (int) env('MAILBOX_TRACKING_LOGS_DEDUPE_SECONDS', 60),
            'retention_days' => (int) env('MAILBOX_TRACKING_LOGS_RETENTION_DAYS', 180),
        ],

        // Per-pixel throttle configuration to avoid abuse (DDoS) of the tracking pixel
        // Format: "<maxAttempts>,<decayMinutes>" (same as Laravel throttle middleware)
        // Example: '30,1' = 30 requests per 1 minute
        'pixel_throttle_enabled' => env('MAILBOX_PIXEL_THROTTLE_ENABLED', true),
        'pixel_throttle' => env('MAILBOX_PIXEL_THROTTLE', '30,1'),
    ],

];

<?php

namespace Bauerdot\FilamentMailBox\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * @property string $key
 * @property string $value
 * @property string|null $type
 */
class MailSetting extends Model
{
    use HasFactory;

    protected $table = 'mail_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'value' => 'string', // Will be cast based on type in accessor
    ];

    // Cache key prefix for settings
    protected static string $cachePrefix = 'filament-mailbox.mail_settings.';

    // Cache ttl in seconds (null = forever)
    protected static $cacheTtl = null;

    /**
     * Get the cast value based on type
     */
    public function getValueAttribute($value)
    {
        return match ($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'array' => json_decode($value, true) ?? [],
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set the value attribute, encoding arrays/json
     */
    public function setValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
            $this->attributes['type'] = 'array';
        } elseif (is_bool($value)) {
            $this->attributes['value'] = $value ? '1' : '0';
            $this->attributes['type'] = 'boolean';
        } elseif (is_int($value)) {
            $this->attributes['value'] = (string) $value;
            $this->attributes['type'] = 'integer';
        } elseif (is_float($value)) {
            $this->attributes['value'] = (string) $value;
            $this->attributes['type'] = 'float';
        } else {
            $this->attributes['value'] = (string) $value;
            $this->attributes['type'] = 'string';
        }
    }

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        // First check cache
        $cacheKey = static::$cachePrefix.$key;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Next, check DB
        $setting = static::where('key', $key)->first();
        if ($setting) {
            $value = $setting->value;
            $ttl = Config::get('filament-mailbox.mail_settings.cache_ttl', static::$cacheTtl);
            Cache::put($cacheKey, $value, $ttl);

            return $value;
        }

        // Fall back to config defaults
        $defaults = Config::get('filament-mailbox.mail_settings.defaults', []);
        if (array_key_exists($key, $defaults)) {
            $value = $defaults[$key];
            // Cache the default as well for consistent reads
            $ttl = Config::get('filament-mailbox.mail_settings.cache_ttl', static::$cacheTtl);
            Cache::put($cacheKey, $value, $ttl);

            return $value;
        }

        return $default;
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value): void
    {
        // Respect lock settings
        $lock = Config::get('filament-mailbox.mail_settings.lock_values', false);
        $defaults = Config::get('filament-mailbox.mail_settings.defaults', []);

        if ($lock && array_key_exists($key, $defaults)) {
            // If locked, don't overwrite default keys
            return;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Update cache
        $cacheKey = static::$cachePrefix.$key;
        $ttl = Config::get('filament-mailbox.mail_settings.cache_ttl', static::$cacheTtl);
        Cache::put($cacheKey, $value, $ttl);

        // Also flush DTO cache so the DTO reflects latest values
        if (class_exists(MailSettingsDto::class)) {
            MailSettingsDto::flushCache();
        }
    }

    /**
     * Get all settings merged with defaults, using cache when possible
     *
     * @return array<string,mixed>
     */
    public static function allCached(): array
    {
        $defaults = Config::get('filament-mailbox.mail_settings.defaults', []);

        $rows = static::all()->pluck('value', 'key')->all();

        // Merge DB values over defaults
        $merged = array_merge($defaults, $rows);

        // Cache each value
        $ttl = Config::get('filament-mailbox.mail_settings.cache_ttl', static::$cacheTtl);
        foreach ($merged as $k => $v) {
            Cache::put(static::$cachePrefix.$k, $v, $ttl);
        }

        return $merged;
    }

    /**
     * Flush cached mail settings
     */
    public static function flushCache(): void
    {
        $keys = array_keys(Config::get('filament-mailbox.mail_settings.defaults', []));
        foreach ($keys as $k) {
            Cache::forget(static::$cachePrefix.$k);
        }

        // Also forget any DB-driven keys
        $rows = static::pluck('key')->all();
        foreach ($rows as $k) {
            Cache::forget(static::$cachePrefix.$k);
        }
        if (class_exists(MailSettingsDto::class)) {
            MailSettingsDto::flushCache();
        }
    }
}

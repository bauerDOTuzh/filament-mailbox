<?php

namespace Bauerdot\FilamentMailLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
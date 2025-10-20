<?php

namespace Bauerdot\FilamentMailBox\Models;

use Illuminate\Database\Eloquent\Model;

class MailOpenEvent extends Model
{
    public $timestamps = false;

    protected $table = 'mail_open_events';

    protected $fillable = [
        'maillog_id',
        'message_id',
        'ip',
        'user_agent',
        'headers',
        'opened_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'opened_at' => 'datetime',
    ];
}

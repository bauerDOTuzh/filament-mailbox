<?php

namespace Bauerdot\FilamentMailBox\Models;

use Bauerdot\FilamentMailBox\Enums\MailStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property array $data
 */
class MailLog extends Model
{
    use HasFactory;

    // Allow mass assignment for common mail log fields. Using fillable is
    // safer than unguarded when publishing a package.
    protected $fillable = [
        'from',
        'to',
        'cc',
        'bcc',
        'subject',
        'body',
        'text_body',
        'headers',
        'attachments',
        'message_id',
        // 'status' autoupdated on proper timestamps updates. Prefer using the accessor
        // 'status',
        'data',
        // timestamp fields for events
        'sent_at',
        'delivered_at',
        'opened_at',
        'bounced_at',
        'complained_at',
    ];

    // Keep casts minimal: data and attachments are JSON, and standard timestamps.
    protected $casts = [
        'data' => 'array',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'status' => MailStatus::class,
    ];

    public function getDataJsonAttribute()
    {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

    /**
     * Mark the mail as sent (sets sent_at). Saves the model.
     */
    public function markSent($when = null): static
    {
        $this->sent_at = $when ?? now();
        $this->setStatusIfHigherPriority(MailStatus::SENT);
        $this->save();

        return $this;
    }

    /**
     * Mark the mail as delivered (sets delivered_at). Saves the model.
     */
    public function markDelivered($when = null): static
    {
        $this->delivered_at = $when ?? now();
        $this->setStatusIfHigherPriority(MailStatus::DELIVERED);
        $this->save();

        return $this;
    }

    /**
     * Mark the mail as opened (sets opened_at). Saves the model.
     */
    public function markOpened($when = null): static
    {
        $this->opened_at = $when ?? now();
        $this->setStatusIfHigherPriority(MailStatus::OPENED);
        $this->save();

        return $this;
    }

    /**
     * Mark the mail as bounced (sets bounced_at). Saves the model.
     */
    public function markBounced($when = null): static
    {
        $this->bounced_at = $when ?? now();
        $this->setStatusIfHigherPriority(MailStatus::BOUNCED);
        $this->save();

        return $this;
    }

    /**
     * Mark the mail as complained (sets complained_at). Saves the model.
     */
    public function markComplained($when = null): static
    {
        $this->complained_at = $when ?? now();
        $this->setStatusIfHigherPriority(MailStatus::COMPLAINED);
        $this->save();

        return $this;
    }

    /**
     * Ensure status is updated to at least the provided status according to
     * priority. Higher priority statuses should not be overwritten by lower
     * priority updates.
     */
    protected function setStatusIfHigherPriority(MailStatus $status): void
    {
        // Define priority order (higher index = higher priority)
        $priority = [
            MailStatus::UNSENT->value => 0,
            MailStatus::SENT->value => 1,
            MailStatus::DELIVERED->value => 2,
            MailStatus::OPENED->value => 3,
            MailStatus::BOUNCED->value => 4,
            MailStatus::COMPLAINED->value => 5,
        ];

        $current = $this->status ?? MailStatus::UNSENT;

        $currentPriority = $priority[$current->value] ?? 0;
        $newPriority = $priority[$status->value] ?? 0;

        if ($newPriority >= $currentPriority) {
            $this->status = $status;
        }
    }
}

<?php

namespace Bauerdot\FilamentMailBox\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MailStatus: string implements HasColor, HasLabel
{
    case BOUNCED = 'bounced';
    case COMPLAINED = 'complained';
    case OPENED = 'opened';
    case DELIVERED = 'delivered';
    case SENT = 'sent';
    case UNSENT = 'unsent';

    public function getLabel(): string
    {
        return match ($this) {
            self::BOUNCED => __('filament-mailbox::filament-mailbox.status.bounced') ?: 'Bounced',
            self::COMPLAINED => __('filament-mailbox::filament-mailbox.status.complained') ?: 'Complained',
            self::OPENED => __('filament-mailbox::filament-mailbox.status.opened') ?: 'Opened',
            self::DELIVERED => __('filament-mailbox::filament-mailbox.status.delivered') ?: 'Delivered',
            self::SENT => __('filament-mailbox::filament-mailbox.status.sent') ?: 'Sent',
            self::UNSENT => __('filament-mailbox::filament-mailbox.status.unsent') ?: 'Unsent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BOUNCED => 'danger',
            self::COMPLAINED => 'danger',
            self::OPENED => 'secondary',
            self::DELIVERED => 'success',
            self::SENT => 'primary',
            self::UNSENT => 'gray',
        };
    }
}

<?php

namespace Bauerdot\FilamentMailBox\Resources\MailLogResource\Widgets;

use Bauerdot\FilamentMailBox\Models\MailLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MailStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    // Allow Filament to auto-discover and register this widget with Livewire.
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $total = MailLog::count();

        if ($total === 0) {
            return [];
        }

        $delivered = MailLog::whereRaw('lower(status) = ?', ['delivered'])->count();
        $opened = MailLog::whereRaw('lower(status) = ?', ['opened'])->count();
        $clicked = MailLog::whereRaw('lower(status) = ?', ['clicked'])->count();
        $bounced = MailLog::whereRaw('lower(status) like ?', ['%bounce%'])->count();

        $makePercent = function (int $count) use ($total) {
            return number_format(($count / $total) * 100, 1) . '%';
        };

        return [
            Stat::make(__('Delivered'), $makePercent($delivered))
                ->label(__('Delivered'))
                ->description($delivered . ' ' . __('of') . ' ' . $total . ' ' . __('emails'))
                ->color('success'),

            Stat::make(__('Opened'), $makePercent($opened))
                ->label(__('Opened'))
                ->description($opened . ' ' . __('of') . ' ' . $total . ' ' . __('emails'))
                ->color('info'),

            Stat::make(__('Clicked'), $makePercent($clicked))
                ->label(__('Clicked'))
                ->description($clicked . ' ' . __('of') . ' ' . $total . ' ' . __('emails'))
                ->color('primary'),

            Stat::make(__('Bounced'), $makePercent($bounced))
                ->label(__('Bounced'))
                ->description($bounced . ' ' . __('of') . ' ' . $total . ' ' . __('emails'))
                ->color('danger'),
        ];
    }
}

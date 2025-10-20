<?php

namespace Bauerdot\FilamentMailBox\Resources\MailLogResource\Widgets;

use Bauerdot\FilamentMailBox\Enums\MailStatus;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MailStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $total = MailLog::count();
        $settings = MailSettingsDto::fromConfigAndModel();

        if ($total === 0) {
            return [];
        }

        $makePercent = function (int $count) use ($total) {
            return number_format(($count / $total) * 100, 1).'%';
        };

        if ($settings->supports_stats === false) {
            $sent = MailLog::where('status', MailStatus::SENT)->count();
            $opened = MailLog::where('status', MailStatus::OPENED)->count();

            return [
                Stat::make(__('filament-mailbox::filament-mailbox.stats.sent_at'), $sent)
                    ->label(__('filament-mailbox::filament-mailbox.stats.sent_at'))
                    ->description($sent.' '.__('filament-mailbox::filament-mailbox.stats.emails'))
                    ->color('success'),

                Stat::make(__('filament-mailbox::filament-mailbox.stats.opened_at'), $makePercent($opened))
                    ->label(__('filament-mailbox::filament-mailbox.stats.opened_at'))
                    ->description($opened.' '.__('filament-mailbox::filament-mailbox.stats.of').' '.$total.' '.__('filament-mailbox::filament-mailbox.stats.emails'))
                    ->color('info'),
            ];
        }

        $delivered = MailLog::where('status', MailStatus::DELIVERED)->count();
        $opened = MailLog::where('status', MailStatus::OPENED)->count();
        $bounced = MailLog::where('status', MailStatus::BOUNCED)->count();

        return [
            Stat::make(__('filament-mailbox::filament-mailbox.stats.delivered_at'), $makePercent($delivered))
                ->label(__('filament-mailbox::filament-mailbox.stats.delivered_at'))
                ->description($delivered.' '.__('filament-mailbox::filament-mailbox.stats.of').' '.$total.' '.__('filament-mailbox::filament-mailbox.stats.emails'))
                ->color('success'),

            Stat::make(__('filament-mailbox::filament-mailbox.stats.opened_at'), $makePercent($opened))
                ->label(__('filament-mailbox::filament-mailbox.stats.opened_at'))
                ->description($opened.' '.__('filament-mailbox::filament-mailbox.stats.of').' '.$total.' '.__('filament-mailbox::filament-mailbox.stats.emails'))
                ->color('info'),

            Stat::make(__('filament-mailbox::filament-mailbox.stats.bounced_at'), $makePercent($bounced))
                ->label(__('filament-mailbox::filament-mailbox.stats.bounced_at'))
                ->description($bounced.' '.__('filament-mailbox::filament-mailbox.stats.of').' '.$total.' '.__('filament-mailbox::filament-mailbox.stats.emails'))
                ->color('danger'),
        ];
    }
}

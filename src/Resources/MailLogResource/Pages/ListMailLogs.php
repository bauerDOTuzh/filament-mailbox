<?php

namespace Bauerdot\FilamentMailBox\Resources\MailLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Enums\MailStatus;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Bauerdot\FilamentMailBox\Resources\MailLogResource;
use Bauerdot\FilamentMailBox\Resources\MailLogResource\Widgets\MailStatsWidget;

class ListMailLogs extends ListRecords
{
    protected static string $resource = MailLogResource::class;

    private $statusesWithoutStats = [
        MailStatus::SENT,
        MailStatus::UNSENT,
        MailStatus::OPENED
    ];

    protected function getHeaderWidgets(): array
    {
        return [
            MailStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $model = MailLog::class;
        
        $settings = MailSettingsDto::fromConfigAndModel();

        $all = Tab::make('all')
            ->label(__('filament-mailbox::filament-mailbox.tabs.all'))
            ->badge($model::count());

        $tabs = [$all];

        // Use MailStatus enum to define tabs. This centralizes labels and ensures a stable set of tabs.
        foreach (MailStatus::cases() as $enum) {
            $statusValue = $enum->value; // e.g. 'delivered'

            //in case support stats = false show only statuses without stats, others are skipped
            if (!$settings->supports_stats && !in_array($statusValue, array_map(fn($s) => $s->value, $this->statusesWithoutStats), true)) {
                continue; // no need to add additional statuses
            }

            $tabs[] = Tab::make($statusValue)
                ->label($enum->getLabel())
                ->badgeColor('primary')
                // badge shows 0 when there are no records
                ->badge(fn () => $model::where('status', $statusValue)->count())
                ->modifyQueryUsing(function (Builder $query) use ($statusValue): Builder {
                    return $query->where('status', $statusValue);
                });
        }

        return $tabs;
    }
}

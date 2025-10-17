<?php

namespace Bauerdot\FilamentMailBox\Resources\MailLogResource\Pages;

use Bauerdot\FilamentMailBox\Resources\MailLogResource;
use Filament\Resources\Pages\ListRecords;
use Bauerdot\FilamentMailBox\Resources\MailLogResource\Widgets\MailStatsWidget;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Tabs\Tabs;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Illuminate\Database\Eloquent\Builder;

class ListMailLogs extends ListRecords
{
    protected static string $resource = MailLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MailStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $model = MailLog::class;

        $all = Tab::make('all')
            ->label(__('All'))
            ->badge($model::count());

        $tabs = [$all];

        $statuses = $model::distinct('status')->pluck('status')->filter()->toArray();

        foreach ($statuses as $status) {
            $key = strtolower(str_replace(' ', '_', $status));

            $tabs[] = Tab::make($key)
                ->label(__($status))
                ->badgeColor('primary')
                ->badge(fn () => $model::where('status', $status)->count())
                ->modifyQueryUsing(function (Builder $query) use ($status): Builder {
                    return $query->where('status', $status);
                });
        }

        return $tabs;
    }
}

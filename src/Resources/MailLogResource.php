<?php

namespace Bauerdot\FilamentMailBox\Resources;

use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailSettingsDto;
use Bauerdot\FilamentMailBox\Resources\MailLogResource\Pages;
use Bauerdot\FilamentMailBox\Resources\MailLogResource\Widgets\MailStatsWidget;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MailLogResource extends Resource
{
    protected static ?string $model = MailLog::class;

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-mailbox.navigation.maillog.register', true);
    }

    public static function getNavigationIcon(): string
    {
        // Ensure we always return a string even if configuration is not published.
        return config('filament-mailbox.navigation.maillog.icon', 'heroicon-o-rectangle-stack');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-mailbox.navigation.maillog.sort');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-mailbox::filament-mailbox.navigation.group');
    }

    public static function getLabel(): string
    {
        return __('filament-mailbox::filament-mailbox.navigation.maillog.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-mailbox::filament-mailbox.navigation.maillog.plural-label');
    }

    public static function infolist(Schema $infolist): Schema
    {
        $settings = MailSettingsDto::fromConfigAndModel();

        $base = [
            Infolists\Components\TextEntry::make('message_id')
                ->label(trans('filament-mailbox::filament-mailbox.column.message_id')),
            Infolists\Components\TextEntry::make('subject')
                ->label(trans('filament-mailbox::filament-mailbox.column.subject')),
            Infolists\Components\TextEntry::make('sent_at')
                ->label(trans('filament-mailbox::filament-mailbox.column.sent_at'))
                ->datetime(),
            Infolists\Components\TextEntry::make('opened_at')
                ->label(trans('filament-mailbox::filament-mailbox.column.opened_at'))
                ->datetime(),
            Infolists\Components\TextEntry::make('to')
                ->label(trans('filament-mailbox::filament-mailbox.column.to')),
            Infolists\Components\TextEntry::make('from')
                ->label(trans('filament-mailbox::filament-mailbox.column.from')),
            Infolists\Components\TextEntry::make('cc')
                ->label(trans('filament-mailbox::filament-mailbox.column.cc')),
            Infolists\Components\TextEntry::make('bcc')
                ->label(trans('filament-mailbox::filament-mailbox.column.bcc')),
            Infolists\Components\TextEntry::make('status')
                ->label(trans('filament-mailbox::filament-mailbox.column.status'))
                ->badge(),
        ];

        // Append stats fields only if driver supports stats
        if ($settings->supports_stats) {
            $base[] = Infolists\Components\TextEntry::make('delivered_at')
                ->label(trans('filament-mailbox::filament-mailbox.column.delivered_at'));
            $base[] = Infolists\Components\TextEntry::make('bounced_at')
                ->label(trans('filament-mailbox::filament-mailbox.column.bounced_at'));
            $base[] = Infolists\Components\TextEntry::make('complaint_at')
                ->label(trans('filament-mailbox::filament-mailbox.column.complaint_at'));
        }

        $tabs = [
            Tab::make('Attachments')
                ->schema([
                    ViewEntry::make('attachments')
                        ->label('')
                        ->view('filament-mailbox::filament.mailLogResource.page.attachments')
                        ->columnSpanFull(),
                ]),
            Tab::make('HTML view')
                ->schema([
                    ViewEntry::make('html_view')
                        ->label('')
                        ->view('filament-mailbox::filament.mailLogResource.page.html_view')
                        ->columnSpanFull(),
                ]),
            Tab::make('Raw view')
                ->schema([
                    ViewEntry::make('raw_view')
                        ->label('')
                        ->view('filament-mailbox::filament.mailLogResource.page.raw_view')
                        ->columnSpanFull(),
                ]),
            Tab::make('Headers')
                ->schema([
                    TextEntry::make('headers')
                        ->label('')
                        ->columnSpanFull(),
                ]),
        ];

        $base[] = Tabs::make('tabs')
            ->tabs($tabs)
            ->columnSpanFull();

        return $infolist->schema($base);
    }

    public static function table(Table $table): Table
    {
        $settings = MailSettingsDto::fromConfigAndModel();

        $columns = [
            TextColumn::make('status')
                ->badge()
                ->label(trans('filament-mailbox::filament-mailbox.column.status')),
            Tables\Columns\TextColumn::make('subject')
                ->label(trans('filament-mailbox::filament-mailbox.column.subject'))
                ->limit(25)
                ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                    $state = $column->getState();
                    if (strlen($state) <= $column->getCharacterLimit()) {
                        return null;
                    }

                    return $state;
                })
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('to')
                ->label(trans('filament-mailbox::filament-mailbox.column.to'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('sent_at')
                ->label(trans('filament-mailbox::filament-mailbox.column.sent_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
        ];

        // If stats are supported, we could add additional columns later. For now the table keeps basic fields.

        return $table
            ->defaultSort(config('filament-mailbox.sort.column', 'created_at'), config('filament-mailbox.sort.direction', 'desc'))
            ->columns($columns)
            ->filters([
                Filter::make('created_at')
                    ->schema([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                \Bauerdot\FilamentMailBox\Resources\MailLogResource\Actions\ResendMailAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailLogs::route('/'),
            'view' => Pages\ViewMailLog::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [MailStatsWidget::class];
    }
}

<?php

namespace Bauerdot\FilamentMailBox\Resources;

use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Resources\MailLogResource\Pages;
use Bauerdot\FilamentMailBox\Resources\MailLogResource\Widgets\MailStatsWidget;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('message_id')
                    ->label(trans('filament-mailbox::filament-mailbox.column.message_id')),
                Infolists\Components\TextEntry::make('subject')
                    ->label(trans('filament-mailbox::filament-mailbox.column.subject')),
                Infolists\Components\TextEntry::make('created_at')
                    ->label(trans('filament-mailbox::filament-mailbox.column.created_at'))
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
                Infolists\Components\TextEntry::make('delivered')
                    ->label(trans('filament-mailbox::filament-mailbox.column.delivered')),
                Infolists\Components\TextEntry::make('opened')
                    ->label(trans('filament-mailbox::filament-mailbox.column.opened')),
                Infolists\Components\TextEntry::make('bounced')
                    ->label(trans('filament-mailbox::filament-mailbox.column.bounced')),
                Infolists\Components\TextEntry::make('complaint')
                    ->label(trans('filament-mailbox::filament-mailbox.column.complaint')),
                Infolists\Components\TextEntry::make('body')
                    ->label(trans('filament-mailbox::filament-mailbox.column.body'))
                    ->view('filament-mailbox::email-html')
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('headers')
                    ->label(trans('filament-mailbox::filament-mailbox.column.headers'))
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('attachments')
                    ->label(trans('filament-mailbox::filament-mailbox.column.attachments'))
                    ->columnSpanFull(),
                // Infolists\Components\Section::make('Data')
                //     ->label(trans('filament-mailbox::filament-mailbox.column.data'))
                //     ->icon('heroicon-m-list-bullet')
                //     ->schema([
                //         Infolists\Components\TextEntry::make('data_json')
                //             ->label(null),
                //     ])
                //     ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(config('filament-mailbox.sort.column', 'created_at'), config('filament-mailbox.sort.direction', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label(trans('filament-mailbox::filament-mailbox.column.status'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label(trans('filament-mailbox::filament-mailbox.column.subject'))
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column content exceeds the length limit.
                        return $state;
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to')
                    ->label(trans('filament-mailbox::filament-mailbox.column.to'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('filament-mailbox::filament-mailbox.column.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(trans('filament-mailbox::filament-mailbox.column.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MailLog::distinct('status')->pluck('status', 'status')->filter()->toArray()),
                Filter::make('created_at')
                    ->form([
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


    /**
     * Normalize various mail recipient shapes and format them for display.
     *
     * Accepts arrays, JSON strings, objects, numeric lists, and associative maps
     * such as {"a.bauer@email.cz": null}.
     *
     * @param mixed $emails
     */

     public static function getWidgets(): array
    {
        return [
             MailStatsWidget::class,
        ];
    }
}

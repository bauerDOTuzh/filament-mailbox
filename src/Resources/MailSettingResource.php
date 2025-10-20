<?php

namespace Bauerdot\FilamentMailBox\Resources;

// use Bauerdot\FilamentMailBox\Resources\Pages\EditMailSettings;
use BackedEnum;
use Bauerdot\FilamentMailBox\Models\MailSetting;
use Bauerdot\FilamentMailBox\Resources\MailSettingResource\Pages\EditMailSettings;
use Bauerdot\FilamentMailBox\Resources\MailSettingResource\Pages\ListMailSettings;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class MailSettingResource extends Resource
{
    protected static ?string $model = MailSetting::class;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationIcon(): string
    {
        return config('filament-mailbox.navigation.settings.icon', 'heroicon-o-cog');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-mailbox.navigation.settings.sort');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-mailbox::filament-mailbox.navigation.group');
    }

    public static function getLabel(): string
    {
        return __('filament-mailbox::filament-mailbox.navigation.settings.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-mailbox::filament-mailbox.navigation.settings.plural-label');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'mail-setting';

    // The resource uses a custom Edit page which provides the form
    public static function getPages(): array
    {
        return [
            'index' => EditMailSettings::route('/'),
            // 'list' => ListMailSettings::route('/list'),
            // 'edit' => Pages\EditMailSettings::route('/'),
        ];
    }
}

<?php

namespace Bauerdot\FilamentMailLog\Resources;

use Filament\Forms;
use Filament\Tables;
// use Bauerdot\FilamentMailLog\Resources\Pages\EditMailSettings;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Bauerdot\FilamentMailLog\Models\MailSetting;
use Bauerdot\FilamentMailLog\Resources\MailSettingResource\Pages\ListMailSettings;
use Bauerdot\FilamentMailLog\Resources\MailSettingResource\Pages\EditMailSettings;

class MailSettingResource extends Resource
{
    protected static ?string $model = MailSetting::class;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationIcon(): string
    {
        return config('filament-maillog.navigation.settings.icon', 'heroicon-o-cog');
    }
    
    public static function getNavigationSort(): ?int
    {
        return config('filament-maillog.navigation.settings.sort');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-maillog::filament-maillog.navigation.group');
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

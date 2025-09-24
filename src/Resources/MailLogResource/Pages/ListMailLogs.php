<?php

namespace Bauerdot\FilamentMailLog\Resources\MailLogResource\Pages;

use Bauerdot\FilamentMailLog\Resources\MailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListMailLogs extends ListRecords
{
    protected static string $resource = MailLogResource::class;
}

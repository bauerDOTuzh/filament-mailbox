<?php

namespace Bauerdot\FilamentMailLog\Resources\MailLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Bauerdot\FilamentMailLog\Resources\MailLogResource;

class ListMailLogs extends ListRecords
{
    protected static string $resource = MailLogResource::class;
}

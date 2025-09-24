<?php

namespace Bauerdot\FilamentMailLog\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bauerdot\FilamentMailLog\FilamentMailLog
 */
class FilamentMailLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bauerdot\FilamentMailLog\FilamentMailLog::class;
    }
}

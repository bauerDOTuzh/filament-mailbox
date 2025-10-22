<?php

namespace Bauerdot\FilamentMailBox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bauerdot\FilamentMailBox\FilamentMailBox
 */
class FilamentMailBox extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bauerdot\FilamentMailBox\FilamentMailBox::class;
    }
}

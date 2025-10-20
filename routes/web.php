<?php

use Bauerdot\FilamentMailBox\Http\Controllers\PixelController;
use Illuminate\Support\Facades\Route;

Route::get('/filament-mailbox/pixel/{id}', [PixelController::class, 'show'])
    ->name('filament-mailbox.pixel');

<?php
use Illuminate\Support\Facades\Route;
use Bauerdot\FilamentMailBox\Http\Controllers\PixelController;

Route::get('/filament-mailbox/pixel/{id}', [PixelController::class, 'show'])
    ->name('filament-mailbox.pixel');

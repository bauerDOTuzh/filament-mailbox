<?php

use Bauerdot\FilamentMailBox\Http\Controllers\PixelController;
use Illuminate\Support\Facades\Route;

$route = Route::get('/filament-mailbox/pixel/{id}', [PixelController::class, 'show'])
    ->name('filament-mailbox.pixel');

// Apply throttle middleware if enabled in config to prevent abuse/DDOS of the tracking pixel
if (config('filament-mailbox.tracking.pixel_throttle_enabled', true)) {
    $throttle = config('filament-mailbox.tracking.pixel_throttle', '30,1');
    $route->middleware('throttle:'.$throttle);
}

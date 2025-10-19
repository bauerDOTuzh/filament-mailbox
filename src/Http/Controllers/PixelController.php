<?php

namespace Bauerdot\FilamentMailBox\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Bauerdot\FilamentMailBox\Models\MailLog;

class PixelController
{
    // 1x1 transparent PNG
    private const PNG =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABJ9k9WQAAAABJRU5ErkJggg==';

    public function show(string $id): Response
    {
        try {
            // Minimal: mark first-open timestamp if the column exists
            $mailLog = MailLog::where('message_id', $id)->first();
            $mailLog->markOpened();
        } catch (\Throwable $e) {
            // Never break image response
            Log::debug($e);
        }

        $png = base64_decode(self::PNG);

        return response($png, 200, [
            'Content-Type'   => 'image/png',
            'Content-Length' => (string) strlen($png),
            // Ask clients/proxies not to cache (proxies may still prefetch)
            'Cache-Control'  => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'         => 'no-cache',
            'Expires'        => '0',
        ]);
    }
}

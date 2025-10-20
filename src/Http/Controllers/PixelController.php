<?php

namespace Bauerdot\FilamentMailBox\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Bauerdot\FilamentMailBox\Models\MailLog;
use Bauerdot\FilamentMailBox\Models\MailOpenEvent;
use Illuminate\Http\Request;

class PixelController
{
    // 1x1 transparent PNG
    private const PNG =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABJ9k9WQAAAABJRU5ErkJggg==';

    public function show(Request $request, string $id): Response
    {
        // Always return the image; do not let tracking failures break the response.
        try {

            if (! config('filament-mailbox.tracking.turn_on', true)) {
                // tracking globally disabled
                return $this->pixelResponse();
            }

            $level = config('filament-mailbox.tracking.level', 'logs');

            // Ensure the message exists; update opened_at on the main log (first-open semantics)
            $mailLog = MailLog::where('message_id', $id)->firstOrFail();
            $mailLog->markOpened();
            

            if ($level === 'clicks' ) {
                $mailLog->increment('opens_count');
                return $this->pixelResponse();
            }

            // 'logs' level: store granular rows in mail_open_events if enabled
            $logsCfg = config('filament-mailbox.tracking.logs', []);
            if (! empty($logsCfg) && ($logsCfg['enabled'] ?? true)) {
                $ip = ($logsCfg['store_ip'] ?? true) ? $request->ip() : null;
                $ua = ($logsCfg['store_user_agent'] ?? true) ? ($request->header('User-Agent') ?? null) : null;
                $hdrs = ($logsCfg['store_headers'] ?? false) ? $request->headers->all() : null;

                $dedupe = (int) ($logsCfg['dedupe_seconds'] ?? 60);
                $shouldInsert = true;

                if ($dedupe > 0 && ($ip || $ua)) {
                    $recent = now()->subSeconds($dedupe);
                    $q = MailOpenEvent::where('maillog_id', $mailLog->id)->where('opened_at', '>=', $recent);
                    if ($ip) {
                        $q->where('ip', $ip);
                    }
                    if ($ua) {
                        $q->where('user_agent', $ua);
                    }
                    if ($q->exists()) {
                        $shouldInsert = false;
                    }
                }

                if ($shouldInsert) {
                    try {
                        MailOpenEvent::create([
                            'maillog_id' => $mailLog->id,
                            'ip' => $ip,
                            'user_agent' => $ua,
                            'headers' => $hdrs,
                            'opened_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        Log::debug($e);
                    }

                    return $this->pixelResponse();
                }
            }
            
        } catch (\Throwable $e) {
            Log::debug($e);
        }

        return $this->pixelResponse();
    }

    private function pixelResponse(): Response
    {
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

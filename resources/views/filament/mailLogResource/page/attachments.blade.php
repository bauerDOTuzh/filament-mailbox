@php
    $record = $getRecord();
    $attachments = $record->attachments ?? [];
    // helper for human readable size
    $humanFilesize = function ($bytes) {
        if (!is_numeric($bytes)) {
            return null;
        }
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    };
@endphp

@if(!empty($attachments))
    <div class="flex flex-wrap gap-8">
        @foreach($attachments as $attachment)
            @php
                $filename = $attachment['name'] ?? $attachment['filename'] ?? __('filament-mailbox::filament-mailbox.attachments.attachment');
                $size = $attachment['size'] ?? null;
                $mediaType = $attachment['media_type'] ?? 'application/octet-stream';
                $hasUrl = !empty($attachment['url']);
                $hasPath = !empty($attachment['path']);
                $hasBody = !empty($attachment['body_base64']);
                $downloadHref = null;

                if ($hasUrl) {
                    $downloadHref = $attachment['url'];
                } elseif ($hasPath) {
                    $downloadHref = \Illuminate\Support\Facades\Storage::disk(config('filament-mailbox.attachments_disk', 'local'))->url($attachment['path']);
                } elseif ($hasBody) {
                    $downloadHref = 'data:'.($mediaType ?: 'application/octet-stream').';base64,'.$attachment['body_base64'];
                }
            @endphp

            <x-filament::section class="text-center grow">
                <x-slot name="heading">
                    <strong>{{ $filename }}</strong>
                </x-slot>

                <div class="text-sm text-gray-600">
                    @if($size)
                        <div>{{ $humanFilesize($size) }}</div>
                    @endif
                    @if(!empty($attachment['headers']))
                        <details class="mt-2 text-left p-2 bg-gray-50 rounded text-xs">
                            <summary class="cursor-pointer text-xs text-gray-700">{{ __('filament-mailbox::filament-mailbox.attachments.headers') }}</summary>
                            <pre class="whitespace-pre-wrap">{{ $attachment['headers'] }}</pre>
                        </details>
                    @endif
                </div>

                <div class="mt-2">
                    @if($downloadHref)
                        <a href="{{ $downloadHref }}"
                           @if(!$hasUrl) download="{{ $filename }}" @endif
                           target="_blank" class="text-sm text-primary-600">{{ __('filament-mailbox::filament-mailbox.attachments.download') }}</a>
                    @else
                        <span class="text-sm text-gray-600">{{ __('filament-mailbox::filament-mailbox.attachments.unavailable') }}</span>
                    @endif
                </div>
            </x-filament::section>
        @endforeach
    </div>
@endif

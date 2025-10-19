@php $record = $getRecord(); $attachments = $record->attachments ?? []; @endphp

@if(!empty($attachments))
    <div class="flex flex-wrap gap-8">
        @foreach($attachments as $attachment)
            <x-filament::section class="text-center grow">
                <x-slot name="heading">
                    <strong>{{ is_array($attachment) ? ($attachment['name'] ?? ($attachment['filename'] ?? __('filament-mailbox::filament-mailbox.attachments.attachment'))) : $attachment }}</strong>
                </x-slot>

                @php
                    $downloadAction = null;
                    // If attachment has a path, prepare a simple download link
                    if (is_array($attachment) && !empty($attachment['path'])) {
                        $downloadAction = '\\Illuminate\\Support\\Facades\\Storage::disk(config(\'filament-mailbox.attachments_disk\', \'local\'))->url(' . var_export($attachment['path'], true) . ');';
                    }
                @endphp

                @if(is_array($attachment) && !empty($attachment['url']))
                    <a href="{{ $attachment['url'] }}" target="_blank" class="text-sm text-primary-600">{{ __('filament-mailbox::filament-mailbox.attachments.download') }}</a>
                @elseif(is_array($attachment) && !empty($attachment['path']))
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk(config('filament-mailbox.attachments_disk', 'local'))->url($attachment['path']) }}" target="_blank" class="text-sm text-primary-600">{{ __('filament-mailbox::filament-mailbox.attachments.download') }}</a>
                @endif
            </x-filament::section>
        @endforeach
    </div>
@endif

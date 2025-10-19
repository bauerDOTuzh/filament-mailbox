@php $record = $record ?? $getRecord(); $html = $record->body ?? ''; @endphp

<x-dynamic-component :component="$getFieldWrapperView()" >
    <div>
        @if (trim($html) === '')
            <div class="p-4 text-sm text-gray-600">{{ __('filament-mailbox::filament-mailbox.preview.no_html') }}</div>
        @else
            <iframe style="width: 100%; height: 75vh;" srcdoc="{!! e($html) !!}" frameborder="0"></iframe>
        @endif
    </div>
</x-dynamic-component>

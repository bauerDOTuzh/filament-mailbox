<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="w-full overflow-hidden">
        @php
            $html = $getState() ?? '';
        @endphp

        @if (trim($html) === '')
            <div class="p-4 text-sm text-gray-600">{{ __('filament-mailbox::filament-mailbox.preview.no_html') }}</div>
        @else
            <iframe srcdoc="{{ $html }}" seamless style="width:100%; height:24rem; display:block;" frameborder="0"></iframe>
        @endif
    </div>
</x-dynamic-component>
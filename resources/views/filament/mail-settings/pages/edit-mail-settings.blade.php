<x-filament-panels::page>
    <form wire:submit.prevent="save">
        <div style="margin-bottom: 1rem;">
            {{ $this->form }}
        </div>

        <div class="pt-10">
            <x-filament::button type="submit">
                {{ __('Save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
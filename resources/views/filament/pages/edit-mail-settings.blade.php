@extends('filament::page')

@section('content')
    <div class="filament-page">
        <form wire:submit.prevent="save">
            {{ $this->form }}
            <div style="margin-top:1rem">
                <button type="submit" class="filament-button filament-button-primary">Save</button>
            </div>
        </form>
    </div>
@endsection

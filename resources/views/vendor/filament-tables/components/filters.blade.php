@php
    use Filament\Tables\Enums\FiltersResetActionPosition;
@endphp

@props([
    'applyAction',
    'form',
    'headingTag' => 'h3',
    'resetActionPosition' => FiltersResetActionPosition::Header,
])

<div {{ $attributes->class(['fi-ta-filters']) }}>

    {{ $form }}

    @if ($applyAction->isVisible() || $resetActionPosition === FiltersResetActionPosition::Footer)
        <div class="fi-ta-filters-actions-ctn">
            @if ($applyAction->isVisible())
                {{ $applyAction }}
            @endif

            @if ($resetActionPosition === FiltersResetActionPosition::Footer)
                <x-filament::button
                    color="danger"
                    wire:click="resetTableFiltersForm"
                >
                    {{ __('filament-tables::table.filters.actions.reset.label') }}
                </x-filament::button>
            @endif
        </div>
    @endif
</div>

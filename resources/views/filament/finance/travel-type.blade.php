@php
    $expense = $getRecord();
@endphp

<div class="flex flex-col items-start gap-1 py-3">
    <x-filament::badge color="gray" :icon="$expense->travel_type->getIcon()">
        {{ $expense->travel_type->getLabel() }}
    </x-filament::badge>

    <x-filament::badge color="gray" :icon="$expense->transportation_type->getIcon()">
        {{ $expense->transportation_type->getLabel() }}
    </x-filament::badge>
</div>

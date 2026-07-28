<x-filament::button
    wire:click="callMountedAction"
    wire:loading.attr="disabled"
    color="primary"
    icon="lucide-upload"
>
    {{ __('participant.import.submit') }}
</x-filament::button>

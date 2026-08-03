<x-filament::button
    wire:click="callMountedAction"
    wire:loading.attr="disabled"
    color="primary"
    icon="lucide-upload"
>
    {{ __('finance.import.submit') }}
</x-filament::button>

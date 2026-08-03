<div class="flex items-center gap-3">
    <x-filament::button
        wire:click="callMountedAction({ another: true })"
        wire:loading.attr="disabled"
        color="gray"
        icon="lucide-plus"
    >
        {{ __('finance.add.submit_another') }}
    </x-filament::button>

    <x-filament::button
        wire:click="callMountedAction"
        wire:loading.attr="disabled"
        color="primary"
        icon="lucide-check"
    >
        {{ __('finance.add.submit') }}
    </x-filament::button>
</div>

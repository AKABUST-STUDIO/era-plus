<template x-teleport="body">
    <div
        x-show="pending !== null"
        x-cloak
        x-transition.opacity
        x-trap.noscroll="pending !== null"
        role="dialog"
        aria-modal="true"
        class="fi-combobox-confirm fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/60 p-4"
        x-on:click.self="cancelPick"
        x-on:keydown.escape.window="if (pending !== null) cancelPick()"
    >
        <div class="w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-900">
            <div class="flex flex-col items-center gap-4 px-6 pt-6 pb-4 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning-100 text-warning-600 dark:bg-warning-500/20 dark:text-warning-400">
                    <x-filament::icon icon="lucide-triangle-alert" class="h-6 w-6" />
                </div>
                <div class="flex flex-col gap-2">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('participant.combobox.confirm_title') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('participant.combobox.confirm_body') }}
                        <span class="font-medium text-gray-950 dark:text-white" x-text="pending"></span>
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 pb-6">
                <x-filament::button
                    color="gray"
                    outlined
                    class="w-full"
                    x-on:click="cancelPick"
                >
                    {{ __('participant.combobox.confirm_cancel') }}
                </x-filament::button>
                <x-filament::button
                    color="warning"
                    class="w-full"
                    x-ref="confirmBtn"
                    x-on:click="confirmPick"
                    x-effect="if (pending !== null) $nextTick(() => $refs.confirmBtn?.focus())"
                >
                    {{ __('participant.combobox.confirm_submit') }}
                </x-filament::button>
            </div>
        </div>
    </div>
</template>

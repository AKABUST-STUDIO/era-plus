<x-filament-panels::page>
    <div class="max-w-2xl mx-auto space-y-6 text-center">
        <x-filament::icon
            icon="lucide-mail-check"
            class="mx-auto h-12 w-12 text-info-500"
        />

        <div class="space-y-2">
            <h2 class="text-2xl font-semibold tracking-tight">
                {{ __('settings.email_verification_required.heading', ['organization' => $organization->name]) }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('settings.email_verification_required.body', ['email' => auth()->user()->email]) }}
            </p>
        </div>

        <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <x-filament::button
                wire:click="resend"
                icon="lucide-send"
                color="primary"
            >
                {{ __('settings.email_verification_required.resend') }}
            </x-filament::button>
            <x-filament::button
                wire:click="refreshStatus"
                icon="lucide-refresh-cw"
                color="gray"
            >
                {{ __('settings.email_verification_required.refresh') }}
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>

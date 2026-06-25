<x-filament-panels::page>
    <div class="max-w-2xl mx-auto space-y-6 text-center">
        <x-filament::icon
            icon="lucide-shield-check"
            class="mx-auto h-12 w-12 text-warning-500"
        />

        <div class="space-y-2">
            <h2 class="text-2xl font-semibold tracking-tight">
                {{ __('settings.two_factor_required.heading', ['organization' => $organization->name]) }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('settings.two_factor_required.body') }}
            </p>
        </div>

        <div class="flex justify-center">
            <x-filament::button
                tag="a"
                :href="\App\Filament\User\Pages\Settings::getUrl(panel: \App\Providers\Filament\UserPanelProvider::PANEL_ID)"
                icon="lucide-key-round"
            >
                {{ __('settings.two_factor_required.set_up') }}
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>

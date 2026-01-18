<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center gap-3 py-24 text-center">
        <x-filament::icon
            icon="lucide-construction"
            class="h-10 w-10 text-gray-400 dark:text-gray-500"
        />

        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
            {{ __('settings.work_in_progress.heading') }}
        </h2>

        <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
            {{ __('settings.work_in_progress.description') }}
        </p>
    </div>
</x-filament-panels::page>

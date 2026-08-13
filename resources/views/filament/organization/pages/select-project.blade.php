<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center gap-3 py-24 text-center">
        <x-filament::icon
            icon="lucide-folder-open"
            class="h-10 w-10 text-gray-400 dark:text-gray-500"
        />

        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
            {{ __('organization.select_project.heading') }}
        </h2>

        <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
            {{ __('organization.select_project.description') }}
        </p>

        <div class="pt-2 text-start">
            @livewire('project-menu-inline')
        </div>
    </div>
</x-filament-panels::page>

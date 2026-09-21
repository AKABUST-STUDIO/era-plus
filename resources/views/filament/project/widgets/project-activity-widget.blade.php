<x-filament-widgets::widget>
    <x-filament::section :heading="$heading" :description="$subtitle">
        @if ($viewAllUrl)
            <x-slot name="afterHeader">
                <x-filament::link :href="$viewAllUrl" wire:navigate size="sm">
                    {{ $viewAllLabel }}
                </x-filament::link>
            </x-slot>
        @endif

        @if ($isEmpty)
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">{{ $emptyMessage }}</p>
        @else
            <div class="flex flex-col divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($entries as $entry)
                    <x-widgets.activity-row
                        :avatarUrl="$entry['avatar_url']"
                        :initials="$entry['initials']"
                        :description="$entry['description']"
                        :when="$entry['when']"
                    />
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

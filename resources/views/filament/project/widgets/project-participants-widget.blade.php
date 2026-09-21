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
                @foreach ($rows as $row)
                    <x-widgets.person-row
                        :name="$row['name']"
                        :avatarUrl="$row['avatar_url']"
                        :initials="$row['initials']"
                    />
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

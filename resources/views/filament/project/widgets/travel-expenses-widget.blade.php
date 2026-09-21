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
                    <div class="flex items-center gap-3 py-2">
                        <span class="inline-flex h-3.5 w-5 shrink-0 items-center justify-center overflow-hidden rounded-[3px] ring-1 ring-gray-950/10">
                            @if (! empty($row['iso2']))
                                <x-filament::icon :icon="'flag-4x3-'.$row['iso2']" class="h-full w-full" />
                            @endif
                        </span>

                        <span class="min-w-0 grow truncate text-sm font-medium text-gray-950 dark:text-white">
                            {{ $row['name'] }}
                        </span>

                        <span class="shrink-0 whitespace-nowrap text-sm font-semibold tabular-nums text-gray-950 dark:text-white">
                            €{{ number_format($row['total_eur'], 2) }}
                        </span>
                    </div>
                @endforeach

                <div class="flex items-center gap-3 pt-3">
                    <span class="grow text-xs text-gray-600 dark:text-gray-400">{{ $sumLabel }}</span>
                    <span class="shrink-0 whitespace-nowrap text-sm font-semibold tabular-nums text-gray-950 dark:text-white">
                        €{{ number_format($total, 2) }}
                    </span>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

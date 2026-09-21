<x-filament-widgets::widget>
    <x-filament::section :heading="$heading" :description="$subtitle">
        @if ($canCreate && $createUrl)
            <x-slot name="afterHeader">
                <x-filament::button
                    tag="a"
                    :href="$createUrl"
                    wire:navigate
                    icon="heroicon-m-plus"
                    size="sm"
                >
                    {{ $newProjectLabel }}
                </x-filament::button>
            </x-slot>
        @endif

        @if ($isEmpty)
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">{{ $emptyMessage }}</p>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($projects as $project)
                    <a
                        href="{{ $project['url'] }}"
                        wire:navigate
                        class="group flex flex-col gap-3.5 rounded-lg border border-gray-200 bg-white p-4 text-inherit no-underline transition hover:border-gray-300 hover:shadow-sm dark:border-white/10 dark:bg-gray-900 dark:hover:border-white/20"
                    >
                        <span class="flex items-center gap-2.5">
                            <span class="text-base font-semibold tracking-tight text-gray-950 dark:text-white">
                                {{ $project['name'] }}
                            </span>
                            @if (filled($project['erasmus_action_label']))
                                <x-filament::badge :color="$project['erasmus_action_color'] ?? 'gray'">
                                    {{ $project['erasmus_action_label'] }}
                                </x-filament::badge>
                            @endif
                        </span>

                        <span class="flex items-center gap-2.5">
                            @if ($project['participant_count'] === 0)
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $noParticipantsLabel }}
                                </span>
                            @else
                                <x-widgets.avatar-stack
                                    :people="$project['participants']"
                                    :extra="$project['extra']"
                                />
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ trans_choice('dashboard.common.participant_count', $project['participant_count'], ['count' => $project['participant_count']]) }}
                                </span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

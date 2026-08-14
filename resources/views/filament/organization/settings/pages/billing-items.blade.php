<x-filament-panels::page>
    <div class="flex flex-col gap-4">
        @forelse ($this->getProjects() as $project)
            <x-filament::section :hasContentEl="false">
                <x-slot name="heading">
                    <div class="flex flex-wrap items-center gap-2">
                        <img
                            src="{{ $project->getAvatarUrl() }}"
                            alt=""
                            class="h-8 w-8 shrink-0 rounded-full object-cover"
                        />

                        <span>{{ $project->name }}</span>

                        <x-filament::badge :color="$this->tierColorFor($project)">
                            {{ $this->tierLabelFor($project) }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                <x-slot name="footer">
                    <div class="flex w-full justify-between items-center gap-4">
                        <span>{{ __('settings.billing_items.item_description', ['name' => $project->name]) }}</span>

                        <x-filament::button
                            tag="a"
                            :href="$this->projectSettingsUrl($project)"
                            color="gray"
                        >
                            {{ __('settings.billing_items.view') }}
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::section>
        @empty
            <p>{{ __('settings.billing_items.empty') }}</p>
        @endforelse
    </div>
</x-filament-panels::page>

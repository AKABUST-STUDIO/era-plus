<x-filament-panels::page>
    <div class="flex flex-col gap-4">
        @forelse ($this->getItems() as $organization)
            <x-filament::section :hasContentEl="false">
                <x-slot name="heading">
                    <div class="flex flex-wrap items-center gap-2">
                        <img
                            src="{{ $organization->getAvatarUrl() }}"
                            alt=""
                            class="h-8 w-8 shrink-0 rounded-full object-cover"
                        />

                        <span>{{ $organization->name }}</span>

                        <x-filament::badge :color="$this->statusBadgeColorFor($organization)">
                            {{ $this->statusLabelFor($organization) }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                <x-slot name="footer">
                    <div class="flex w-full justify-between items-center">
                        {{ __('user.billing.items.item_description', ['name' => $organization->name]) }}
                        <x-filament::button
                            tag="a"
                            :href="$this->organizationBillingUrl($organization)"
                            color="gray"
                        >
                            {{ __('user.billing.items.view') }}
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::section>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('user.billing.items.empty') }}
            </p>
        @endforelse
    </div>
</x-filament-panels::page>

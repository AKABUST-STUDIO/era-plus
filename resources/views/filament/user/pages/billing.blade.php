<x-filament-panels::page>
    @php
        $organizations = $this->getOwnedOrganizations();
    @endphp

    @if ($organizations->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('user.billing.empty') }}
            </p>
            <a href="{{ \Filament\Facades\Filament::getPanel('organization')->getTenantRegistrationUrl() }}"
               class="mt-4 inline-flex items-center gap-1 text-primary-600 hover:underline text-sm">
                {{ __('user.organizations.create.action') }}
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($organizations as $organization)
                <x-filament::section>
                    <x-slot name="heading">{{ $organization->name }}</x-slot>
                    <x-slot name="description">{{ $this->cardLabel() }}</x-slot>

                    <div class="flex flex-wrap gap-3 items-center">
                        <x-filament::badge>{{ $this->planLabel($organization) }}</x-filament::badge>

                        @if ($this->hasBillingAccount())
                            <x-filament::button wire:click="manage({{ $organization->id }})" size="sm">
                                {{ __('user.billing.manage') }}
                            </x-filament::button>
                        @endif

                        @if ($this->isBasicTier($organization))
                            @if (filled(config('services.stripe.prices.pro')))
                                <x-filament::button color="primary" size="sm" wire:click="upgrade({{ $organization->id }}, 'pro')">
                                    {{ __('user.billing.upgrade_pro') }}
                                </x-filament::button>
                            @endif
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

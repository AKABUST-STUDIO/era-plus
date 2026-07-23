<x-filament-panels::page>
    {{ $this->inviteForm }}

    <x-filament::tabs class="fi-users-tabs">
        @foreach ($this->getTabs() as $key => $tab)
            <x-filament::tabs.item
                :active="$activeTab === $key"
                :badge="$tab['count']"
                wire:click="switchTab({{ \Illuminate\Support\Js::from($key) }})"
            >
                {{ $tab['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>

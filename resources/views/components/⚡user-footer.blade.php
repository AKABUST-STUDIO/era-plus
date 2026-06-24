<?php

use App\Facades\OrganizationService;
use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public function user(): ?User
    {
        return auth()->user();
    }

    public function avatarUrl(): string
    {
        $user = $this->user();

        if ($user === null) {
            return 'https://ui-avatars.com/api/?name=?&size=128';
        }

        return $user->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&size=128';
    }

    public function accountUrl(): string
    {
        return route('filament.user.pages.dashboard');
    }

    public function showUpgrade(): bool
    {
        return OrganizationService::shouldShowUpgradeCta();
    }
};
?>

@php($user = $this->user())

<div class="px-3 py-3 border-t border-gray-200 dark:border-white/10">
    @if ($user !== null)
        <x-filament::dropdown placement="top-start">
            <x-slot name="trigger">
                <button
                    type="button"
                    class="w-full flex items-center gap-3 rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-white/5 transition"
                >
                    <img
                        src="{{ $this->avatarUrl() }}"
                        alt="{{ $user->name }}"
                        class="size-8 rounded-full object-cover shrink-0"
                    >
                    <div class="text-left min-w-0">
                        <div class="text-sm font-medium truncate">{{ $user->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                    </div>
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item
                    :href="$this->accountUrl()"
                    icon="lucide-user"
                    tag="a"
                >
                    {{ __('user.menu.account') }}
                </x-filament::dropdown.list.item>

                @if ($this->showUpgrade())
                    <x-filament::dropdown.list.item
                        :href="route('filament.organization-settings.pages.billing', ['organization' => OrganizationService::current()?->slug])"
                        icon="lucide-sparkles"
                        tag="a"
                    >
                        {{ __('user.menu.upgrade') }}
                    </x-filament::dropdown.list.item>
                @endif

                <x-filament::dropdown.list.item
                    icon="lucide-log-out"
                    :form="filament()->getCurrentOrDefaultPanel()?->getLogoutFormActionUrl()"
                >
                    {{ __('user.menu.logout') }}
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    @endif
</div>

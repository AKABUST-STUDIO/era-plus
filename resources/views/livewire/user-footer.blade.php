<div class="px-3 py-3 border-t border-gray-200 dark:border-white/10 user-footer">
    @if ($user !== null)
        <x-filament::dropdown placement="top-start">
            <x-slot name="trigger">
                <button
                    type="button"
                    class="w-full flex items-center gap-3 rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-white/5 transition"
                >
                    <x-filament-panels::avatar.user :user="$user" class="size-8 shrink-0" />
                    <div class="text-left min-w-0">
                        <div class="text-sm font-medium truncate">{{ $user->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                    </div>
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item
                    :href="$accountUrl"
                    icon="lucide-user"
                    tag="a"
                >
                    {{ __('user.menu.account') }}
                </x-filament::dropdown.list.item>

                @if ($showUpgrade && $upgradeUrl)
                    <x-filament::dropdown.list.item
                        :href="$upgradeUrl"
                        icon="lucide-sparkles"
                        tag="a"
                    >
                        {{ __('user.menu.upgrade') }}
                    </x-filament::dropdown.list.item>
                @endif

                <x-filament::dropdown.list.item
                    icon="lucide-log-out"
                    :form="$logoutFormAction"
                >
                    {{ __('user.menu.logout') }}
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    @endif
</div>

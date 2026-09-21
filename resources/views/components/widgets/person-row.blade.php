@props([
    'name',
    'avatarUrl' => null,
    'initials' => '·',
    'isPending' => false,
    'roleLabel' => null,
    'isAdminRole' => false,
    'avatarSize' => 'size-8',
])

<div class="flex items-center gap-3 py-2">
    @if (filled($avatarUrl))
        <img src="{{ $avatarUrl }}" alt="" class="{{ $avatarSize }} shrink-0 rounded-full object-cover" />
    @else
        <span aria-hidden="true" class="{{ $avatarSize }} flex shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">
            {{ $initials }}
        </span>
    @endif

    <div class="flex min-w-0 grow items-center gap-2">
        <span class="truncate text-sm font-medium text-gray-950 dark:text-white">
            {{ $name }}
        </span>
        @if ($isPending)
            <x-filament::badge color="warning" icon="lucide-clock">
                {{ __('dashboard.common.pending') }}
            </x-filament::badge>
        @endif
    </div>

    @if (filled($roleLabel))
        <x-filament::badge
            :color="$isAdminRole ? 'primary' : 'gray'"
            class="flex-none"
        >
            {{ $roleLabel }}
        </x-filament::badge>
    @endif
</div>

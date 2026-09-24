@props(['record'])

@php
    $isRegistered = $record instanceof \App\Models\Organization;
    $customAvatar = $isRegistered && method_exists($record, 'getFilamentAvatarUrl')
        ? $record->getFilamentAvatarUrl()
        : null;
    $avatarUrl = $customAvatar
        ?: 'https://ui-avatars.com/api/?name='.urlencode($record->name ?? '?').'&size=128';
@endphp

<div class="flex w-full items-center justify-between gap-3 py-1">
    <div class="flex min-w-0 grow items-center gap-3">
        <img src="{{ $avatarUrl }}" alt="" class="h-8 w-8 shrink-0 rounded-full" />
        <span class="truncate text-sm font-medium text-gray-950 dark:text-white">
            {{ $record->name }}
        </span>
    </div>

    @if ($isRegistered)
        <span class="inline-flex shrink-0 items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
            <x-filament::icon icon="lucide-badge-check" class="h-3.5 w-3.5" />
            {{ __('participant.select_option.verified') }}
        </span>
    @endif
</div>

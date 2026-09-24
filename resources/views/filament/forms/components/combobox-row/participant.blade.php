@php
    $record = $item;
    $verified = ($record instanceof \App\Models\User) && $record->email_verified_at !== null;
@endphp

<img
    src="{{ $record->avatarUrl() }}"
    alt=""
    class="h-8 w-8 shrink-0 rounded-full"
/>
<span class="flex min-w-0 grow flex-col gap-0.5 leading-tight">
    <span class="text-sm font-medium text-gray-950 dark:text-white">
        {{ $record->name }}
    </span>
    @if (filled($record->email))
        <span class="truncate text-xs text-gray-500 dark:text-gray-400">
            {{ $record->email }}
        </span>
    @endif
</span>
@if ($verified)
    <span class="inline-flex shrink-0 items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
        <x-filament::icon icon="lucide-badge-check" class="h-3.5 w-3.5" />
        Verified
    </span>
@else
    <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">
        Not registered
    </span>
@endif

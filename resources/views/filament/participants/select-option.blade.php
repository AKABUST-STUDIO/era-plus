@props(['record'])

@php
    $isUser = $record instanceof \App\Models\User;
    $verified = $isUser && $record->email_verified_at !== null;
@endphp

<div class="flex w-full items-center justify-between gap-3 py-1">
    <div class="flex min-w-0 grow items-center gap-3">
        <img
            src="{{ $record->avatarUrl() }}"
            alt=""
            class="h-8 w-8 shrink-0 rounded-full"
        />
        <div class="flex min-w-0 grow flex-col gap-0.5 leading-tight">
            <span class="text-sm font-medium text-gray-950 dark:text-white">
                {{ $record->name }}
            </span>
            @if (filled($record->email))
                <span class="truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ $record->email }}
                </span>
            @endif
        </div>
    </div>

    @if ($verified)
        <span class="inline-flex shrink-0 items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
            <x-filament::icon icon="lucide-badge-check" class="h-3.5 w-3.5" />
            {{ __('participant.select_option.verified') }}
        </span>
    @else
        <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">
            {{ __('participant.select_option.not_registered') }}
        </span>
    @endif
</div>

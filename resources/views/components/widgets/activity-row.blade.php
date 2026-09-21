@props([
    'avatarUrl' => null,
    'initials' => 'SY',
    'description',
    'when' => '',
])

<div class="flex w-full items-center gap-3 py-2">
    @if (filled($avatarUrl))
        <img src="{{ $avatarUrl }}" alt="" class="size-7 shrink-0 rounded-full object-cover" />
    @else
        <span aria-hidden="true" class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">
            {{ $initials }}
        </span>
    @endif

    <span class="min-w-0 grow truncate text-sm text-gray-600 dark:text-gray-300">
        {!! $description !!}
    </span>

    @if (filled($when))
        <span class="ms-auto shrink-0 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
            {{ $when }}
        </span>
    @endif
</div>

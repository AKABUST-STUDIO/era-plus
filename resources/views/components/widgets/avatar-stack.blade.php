@props([
    'people' => [],
    'extra' => 0,
])

<span aria-hidden="true" class="flex items-center">
    @foreach ($people as $index => $person)
        @if (! empty($person['avatar_url']))
            <img
                src="{{ $person['avatar_url'] }}"
                alt=""
                @class([
                    'size-6 rounded-full object-cover ring-2 ring-white dark:ring-gray-900',
                    '-ml-2' => $index > 0,
                ])
                title="{{ $person['name'] ?? '' }}"
            />
        @else
            <span
                @class([
                    'flex size-6 items-center justify-center rounded-full bg-gray-200 text-[10px] font-semibold text-gray-700 ring-2 ring-white dark:bg-white/10 dark:text-gray-200 dark:ring-gray-900',
                    '-ml-2' => $index > 0,
                ])
                title="{{ $person['name'] ?? '' }}"
            >
                {{ $person['initials'] ?? '' }}
            </span>
        @endif
    @endforeach

    @if ($extra > 0)
        <span class="-ml-2 flex size-6 items-center justify-center rounded-full bg-gray-100 text-[10px] font-semibold text-gray-700 ring-2 ring-white dark:bg-white/10 dark:text-gray-200 dark:ring-gray-900">
            +{{ $extra }}
        </span>
    @endif
</span>

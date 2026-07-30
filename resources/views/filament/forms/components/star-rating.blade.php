@php
    $statePath = $getStatePath();
    $current = (int) ($getState() ?? 0);
@endphp

<div
    x-data="{ hover: 0, value: @js($current) }"
    x-on:reset-star-rating.window="value = 0; hover = 0"
    class="flex items-center justify-center gap-2 py-2"
>
    @for ($i = 1; $i <= 5; $i++)
        <button
            type="button"
            x-on:mouseenter="hover = {{ $i }}"
            x-on:mouseleave="hover = 0"
            x-on:click="value = {{ $i }}; $wire.set(@js($statePath), {{ $i }})"
            x-bind:class="((hover === 0 && value >= {{ $i }}) || (hover > 0 && hover >= {{ $i }}))
                ? 'text-amber-400'
                : 'text-gray-300 dark:text-white/20'"
            class="text-5xl leading-none transition-colors duration-100 focus:outline-none"
            aria-label="{{ $i }} / 5"
        >
            &#9733;
        </button>
    @endfor
</div>

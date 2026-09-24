@props([
    'url' => null,
    'tooltip' => null,
    'size' => 'w-32',
])

@php
    $tooltipAttr = filled($tooltip)
        ? 'x-tooltip="{ content: '.\Illuminate\Support\Js::from($tooltip)->toHtml().', theme: $store.theme }"'
        : '';

    $classes = 'aspect-square '.$size.' rounded-full object-cover ring-1 ring-gray-200 dark:ring-white/10 cursor-not-allowed';
@endphp

@if (filled($url))
    <img src="{{ $url }}" alt="" {!! $tooltipAttr !!} class="{{ $classes }}" />
@else
    <div {!! $tooltipAttr !!} class="{{ $classes }} flex items-center justify-center bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-1/2 w-1/2">
            <circle cx="12" cy="8" r="4" />
            <path d="M20 21a8 8 0 0 0-16 0" />
        </svg>
    </div>
@endif

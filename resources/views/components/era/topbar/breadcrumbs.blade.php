@props([
    'title' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class(['flex min-w-0 items-center gap-2']) }} style="max-width: 420px;">
    @if ($breadcrumbs !== [])
        <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" />
    @elseif (filled($title))
        <h1 class="truncate text-[15px] font-semibold text-gray-950 dark:text-white" title="{{ $title }}">
            {{ $title }}
        </h1>
    @endif
</div>

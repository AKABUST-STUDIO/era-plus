@php
    use Illuminate\View\ComponentAttributeBag;
@endphp

<div
    x-data="{ mac: /Mac|iPhone|iPad|iPod/.test(navigator.platform) }"
    class="fi-sidebar-spotlight-search -m-1.5"
>
    <button
        type="button"
        x-on:click="$dispatch('toggle-spotlight')"
        class="flex w-full items-center gap-2 rounded-lg bg-gray-950/5 px-2.5 py-1.5 text-sm text-gray-500 transition hover:bg-gray-950/10 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-500 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10"
    >
        {{
            \Filament\Support\generate_icon_html(
                'lucide-search',
                attributes: new ComponentAttributeBag(['class' => 'size-4 shrink-0']),
            )
        }}

        <span class="flex-1 text-start">{{ __('menu.spotlight.placeholder') }}</span>

        <kbd class="ms-auto inline-flex items-center rounded border border-gray-950/10 bg-white px-1 py-px text-[10px] font-medium text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400">
            <span x-show="mac" x-cloak>⌘K</span>
            <span x-show="! mac" x-cloak>Ctrl K</span>
        </kbd>
    </button>
</div>

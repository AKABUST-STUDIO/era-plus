@php
    /** @var \App\Filament\Forms\Components\FilterGroupDropdown $schemaComponent */
    $activeCount = $schemaComponent->getActiveCount();
    $icon = $schemaComponent->getIcon();
@endphp

<x-filament::dropdown
    placement="bottom-start"
    width="xs"
    class="fi-filter-group-dropdown"
>
    <x-slot name="trigger">
        <button
            type="button"
            @class([
                'fi-icon-btn fi-size-md fi-color-gray relative',
                'fi-filter-group-dropdown-trigger-active' => $activeCount > 0,
            ])
        >
            {!!
                \Filament\Support\generate_icon_html(
                    $icon,
                    attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'size-5']),
                )?->toHtml()
            !!}

            @if ($activeCount > 0)
                <span class="absolute -top-0.5 -end-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-primary-600 px-1 text-[10px] font-semibold leading-4 text-white ring-2 ring-white dark:ring-gray-900">
                    {{ $activeCount }}
                </span>
            @endif
        </button>
    </x-slot>

    <div class="flex flex-col gap-3 p-3">
        {!! $schemaComponent->getChildSchema()?->toHtml() !!}
    </div>
</x-filament::dropdown>

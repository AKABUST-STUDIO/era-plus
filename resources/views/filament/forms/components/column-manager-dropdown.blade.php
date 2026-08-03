@php
    /** @var \App\Filament\Forms\Components\ColumnManagerDropdown $schemaComponent */
    $table = $schemaComponent->getTable();
    $hiddenCount = $schemaComponent->getHiddenCount();
    $icon = $schemaComponent->getIcon();
@endphp

<x-filament::dropdown
    placement="bottom-start"
    :width="$table->getColumnManagerWidth()"
    :max-height="$table->getColumnManagerMaxHeight()"
    wire:key="{{ $schemaComponent->getKey() }}.column-manager"
    class="fi-ta-col-manager-dropdown"
>
    <x-slot name="trigger">
        <button
            type="button"
            @class([
                'fi-icon-btn fi-size-md fi-color-gray relative',
                'fi-column-manager-dropdown-trigger-active' => $hiddenCount > 0,
            ])
        >
            {!!
                \Filament\Support\generate_icon_html(
                    $icon,
                    attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'size-5']),
                )?->toHtml()
            !!}

            @if ($hiddenCount > 0)
                <span class="absolute -top-0.5 -end-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-primary-600 px-1 text-[10px] font-semibold leading-4 text-white ring-2 ring-white dark:ring-gray-900">
                    {{ $hiddenCount }}
                </span>
            @endif
        </button>
    </x-slot>

    <div class="p-6">
        <x-filament-tables::column-manager
            :apply-action="$schemaComponent->getApplyAction()"
            :columns="$table->getColumnManagerColumns()"
            :has-reorderable-columns="$table->hasReorderableColumns()"
            :has-toggleable-columns="$table->hasToggleableColumns()"
            :reorder-animation-duration="$table->getReorderAnimationDuration()"
        />
    </div>
</x-filament::dropdown>

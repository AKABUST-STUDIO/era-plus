@php
    /** @var \App\Filament\Forms\Components\CheckToggle $field */
    $stateExpression = $field->getStateExpression();
@endphp

<div x-data="{ state: {{ $stateExpression }} }">
    <button
        type="button"
        x-on:click="state = ! state"
        x-bind:aria-pressed="state ? 'true' : 'false'"
        class="flex w-full items-center justify-between gap-3 rounded-lg px-2 py-1.5 text-start text-sm font-medium text-gray-950 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
    >
        <span>{{ $field->getLabel() }}</span>

        <span x-cloak x-show="state" class="text-primary-600 dark:text-primary-400">
            {!!
                \Filament\Support\generate_icon_html(
                    'lucide-check',
                    attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'size-4']),
                )?->toHtml()
            !!}
        </span>
    </button>
</div>

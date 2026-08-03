@props(['icon' => null, 'label'])

<span class="flex items-center gap-2">
    @if (filled($icon))
        {!!
            \Filament\Support\generate_icon_html(
                $icon,
                attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'size-4 text-gray-400 dark:text-gray-500']),
            )?->toHtml()
        !!}
    @endif

    <span>{{ $label }}</span>
</span>

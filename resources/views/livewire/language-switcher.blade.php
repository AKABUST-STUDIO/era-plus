<div>
    <x-filament::dropdown placement="left-end" size="sm">
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-dropdown-list-item w-full cursor-pointer"
                aria-label="{{ __('user.menu.language') }}"
            >
                {{
                    \Filament\Support\generate_icon_html('flag-4x3-'.$currentFlag, attributes: new \Illuminate\View\ComponentAttributeBag([
                        'class' => 'h-4 w-6 rounded-sm shadow-sm',
                    ]))
                }}
                <span class="fi-dropdown-list-item-label">
                    {{ __('user.menu.language') }}
                </span>
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($locales as $code => $meta)
                <x-filament::dropdown.list.item
                    :icon="'flag-4x3-'.$meta['flag']"
                    :color="$code === $current ? 'primary' : 'gray'"
                    wire:click="setLocale('{{ $code }}')"
                >
                    {{ $meta['name'] }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>

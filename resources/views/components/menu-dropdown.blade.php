@props([
    'extraClass' => '',
    'tooltip' => null,
    'avatar' => null,
    'label' => '',
    'name' => '—',
    'groups' => [],
])

@php
    use Filament\Support\Icons\Heroicon;
    use Filament\View\PanelsIconAlias;
    use Illuminate\View\ComponentAttributeBag;

    $isSidebarCollapsibleOnDesktop = \Filament\Facades\Filament::isSidebarCollapsibleOnDesktop();
@endphp

<x-filament::dropdown
    placement="bottom-start"
    size
    :class="trim('fi-tenant-menu '.$extraClass)"
>
    <x-slot name="trigger">
        <button
            @if ($isSidebarCollapsibleOnDesktop)
                x-data="{ tooltip: false }"
                x-effect="
                    tooltip = $store.sidebar.isOpen
                        ? false
                        : {
                              content: @js($tooltip),
                              placement: document.dir === 'rtl' ? 'left' : 'right',
                              theme: $store.theme,
                          }
                "
                x-tooltip.html="tooltip"
            @endif
            type="button"
            class="fi-tenant-menu-trigger"
        >
            @if ($avatar)
                <x-filament-panels::avatar.tenant
                    :tenant="$avatar"
                    loading="lazy"
                />
            @endif

            <span
                @if ($isSidebarCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                @endif
                class="fi-tenant-menu-trigger-text"
            >
                <span class="fi-tenant-menu-trigger-current-tenant-label">
                    {{ $label }}
                </span>

                <span class="fi-tenant-menu-trigger-tenant-name">
                    {{ $name }}
                </span>
            </span>

            {{
                \Filament\Support\generate_icon_html(
                    Heroicon::ChevronDown,
                    alias: PanelsIconAlias::TENANT_MENU_TOGGLE_BUTTON,
                    attributes: new ComponentAttributeBag([
                        'x-show' => $isSidebarCollapsibleOnDesktop ? '$store.sidebar.isOpen' : null,
                    ]),
                )
            }}
        </button>
    </x-slot>

    @foreach ($groups as $group)
        @continue (blank($group))

        <x-filament::dropdown.list>
            @foreach ($group as $item)
                <x-filament::dropdown.list.item
                    :href="$item['url']"
                    :icon="$item['icon'] ?? null"
                    :image="$item['image'] ?? null"
                    :tag="$item['tag'] ?? 'a'"
                >
                <div class="inline-flex flex-col">
                    <span>
                            {{ $item['name'] }}
                            
                        </span>
                        <span class="text-gray-100">
                            {{ $item['name'] }}
                            {{ $item['name'] }}

                        </span>
                    </div>
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    @endforeach
</x-filament::dropdown>

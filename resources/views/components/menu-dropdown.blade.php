@props([
    'extraClass' => '',
    'tooltip' => null,
    'avatar' => null,
    'label' => '',
    'name' => '—',
    'badge' => null,
    'items' => [],
    'emptyMessage' => null,
    'create' => null,
    'searchPlaceholder' => null,
    'noMatchesMessage' => null,
    'wide' => true,
    'menuType' => null,
    'url' => null,
    'toggleLabel' => null,
])

@php
    use Filament\Support\Icons\Heroicon;
    use Filament\View\PanelsIconAlias;
    use Illuminate\View\ComponentAttributeBag;

    $isSidebarCollapsibleOnDesktop = \Filament\Facades\Filament::isSidebarCollapsibleOnDesktop();

    $triggerTag = $url ? 'a' : 'button';

    $triggerAttributes = (new ComponentAttributeBag(
        $url
            ? [
                'href' => $url,
                'wire:navigate.hover' => '',
                'x-on:mousedown.stop' => '',
                'x-on:keyup.enter.stop' => '',
                'x-on:keyup.space.stop' => '',
            ]
            : ['type' => 'button'],
    ))->class([
        'fi-tenant-menu-trigger flex items-center gap-2 rounded-lg px-2 py-1.5 text-start no-underline transition hover:bg-gray-950/5 focus-visible:bg-gray-950/5 focus-visible:outline-hidden dark:hover:bg-white/5 dark:focus-visible:bg-white/5',
        'min-w-0 flex-1' => $url,
        'w-full' => $wide && ! $url,
    ]);
@endphp

<x-filament::dropdown
    placement="bottom-start"
    width="sm"
    :class="trim('fi-tenant-menu -m-2 '.$extraClass)"
>
    <x-slot name="trigger">
        <div @class(['flex items-center gap-0.5', 'w-full' => $wide])>
            <{{ $triggerTag }}
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
                {{ $triggerAttributes }}
            >
                @if ($avatar)
                    <x-filament-panels::avatar.tenant
                        :tenant="$avatar"
                        loading="lazy"
                        class="size-6! shrink-0"
                    />
                @endif

                <span
                    @if ($isSidebarCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-tenant-menu-trigger-text inline-flex min-w-0 flex-1 items-center gap-1.5"
                >
                    <span class="fi-tenant-menu-trigger-tenant-name truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ $name }}
                    </span>

                    @if (! blank($badge))
                        <span class="fi-tenant-menu-trigger-badge inline-flex shrink-0 items-center rounded-full bg-gray-950/5 px-1.5 py-px text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $badge }}
                        </span>
                    @endif
                </span>

                @unless ($url)
                    {{
                        \Filament\Support\generate_icon_html(
                            Heroicon::ChevronUpDown,
                            alias: PanelsIconAlias::TENANT_MENU_TOGGLE_BUTTON,
                            attributes: new ComponentAttributeBag([
                                'x-show' => $isSidebarCollapsibleOnDesktop ? '$store.sidebar.isOpen' : null,
                                'class' => 'size-4 shrink-0 ms-auto text-gray-400 dark:text-gray-500',
                            ]),
                        )
                    }}
                @endunless
            </{{ $triggerTag }}>

            @if ($url)
                <button
                    @if ($isSidebarCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    type="button"
                    aria-label="{{ $toggleLabel ?? $label }}"
                    class="fi-tenant-menu-toggle shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-950/5 focus-visible:bg-gray-950/5 focus-visible:outline-hidden dark:text-gray-500 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                >
                    {{
                        \Filament\Support\generate_icon_html(
                            Heroicon::ChevronUpDown,
                            alias: PanelsIconAlias::TENANT_MENU_TOGGLE_BUTTON,
                            attributes: new ComponentAttributeBag([
                                'class' => 'size-4 shrink-0',
                            ]),
                        )
                    }}
                </button>
            @endif
        </div>
    </x-slot>

    <div
        x-init="
            const panel = $el.closest('.fi-dropdown-panel');
            if (panel) {
                new MutationObserver(() => {
                    if (panel.style.display === 'block') {
                        queueMicrotask(() => $refs.search?.focus());
                    }
                }).observe(panel, { attributeFilter: ['style'] });
            }
        "
        x-data="{
            query: '',
            switchTenant(slug) {
                const parts = window.location.pathname.split('/').filter(Boolean);
                const type = @js($menuType);
                let newPath;
                if (type === 'organization') {
                    if (parts.length === 0) {
                        newPath = '/' + slug + '/overview';
                    } else {
                        parts[0] = slug;
                        if (parts.length >= 3) {
                            parts.length = 1;
                            parts.push('overview');
                        }
                        newPath = '/' + parts.join('/');
                    }
                } else if (type === 'project') {
                    if (parts.length >= 3) {
                        parts[1] = slug;
                        newPath = '/' + parts.join('/');
                    } else if (parts.length >= 1) {
                        newPath = '/' + parts[0] + '/' + slug + '/overview';
                    }
                }
                if (newPath) {
                    Livewire.navigate(newPath);
                }
            },
        }"
        class="fi-tenant-menu-panel flex flex-col gap-1.5 p-1.5"
    >
        <div class="fi-tenant-menu-search pt-0.5 px-1">
            <input
                type="text"
                x-model="query"
                x-ref="search"
                autocomplete="off"
                placeholder="{{ $searchPlaceholder ?? __('menu.search') }}"
                class="fi-tenant-menu-search-input w-full border-0 bg-transparent px-2 py-1.5 text-sm text-gray-950 outline-hidden placeholder:text-gray-400 dark:text-white dark:placeholder:text-gray-500"
            />
        </div>

        <div class="fi-tenant-menu-list flex max-h-96 flex-col gap-px overflow-y-auto border-t border-gray-950/5 pt-1.5 dark:border-white/10">
            @foreach ($items as $item)
                <a
                    href="{{ $item['url'] }}"
                    @if ($menuType && ! empty($item['slug']))
                        x-on:click.prevent="switchTenant({{ \Illuminate\Support\Js::from($item['slug']) }})"
                    @else
                        wire:navigate.hover
                    @endif
                    x-show="query === '' || {{ \Illuminate\Support\Js::from(mb_strtolower($item['name'])) }}.includes(query.toLowerCase())"
                    @class([
                        'fi-tenant-menu-item flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-800 no-underline transition hover:bg-gray-950/5 focus-visible:bg-gray-950/5 focus-visible:outline-hidden dark:text-gray-200 dark:hover:bg-white/5 dark:focus-visible:bg-white/5',
                        'fi-tenant-menu-item-current' => $item['isCurrent'] ?? false,
                    ])
                >
                    @if (! empty($item['image']))
                        <div
                            class="fi-tenant-menu-item-image size-5 shrink-0 rounded-full bg-gray-950/10 bg-cover bg-center"
                            style="background-image: url('{{ $item['image'] }}')"
                        ></div>
                    @endif

                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="fi-tenant-menu-item-name min-w-0 truncate font-medium">{{ $item['name'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="fi-tenant-menu-item-badge inline-flex shrink-0 items-center rounded-full bg-gray-950/5 px-1.5 py-px text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                {{ $item['badge'] }}
                            </span>
                        @endif
                    </div>

                    @if ($item['isCurrent'] ?? false)
                        {{
                            \Filament\Support\generate_icon_html(
                                Heroicon::Check,
                                attributes: new ComponentAttributeBag([
                                    'class' => 'fi-tenant-menu-item-check size-4 shrink-0 text-gray-500',
                                ]),
                            )
                        }}
                    @endif
                </a>
            @endforeach

            @if (blank($items))
                <p class="fi-tenant-menu-empty-state px-2 py-2 text-xs text-gray-500">{{ $emptyMessage }}</p>
            @else
                <p
                    x-show="query !== '' && ! $el.parentElement.querySelector('.fi-tenant-menu-item:not([style*=\'display: none\'])')"
                    x-cloak
                    class="fi-tenant-menu-empty-state px-2 py-2 text-xs text-gray-500"
                >
                    {{ $noMatchesMessage ?? __('menu.no_matches') }}
                </p>
            @endif
        </div>

        @if ($create)
            <a
                href="{{ $create['url'] }}"
                wire:navigate.hover
                class="fi-tenant-menu-create flex items-start gap-2.5 rounded-b-md border-t border-gray-950/5 px-2 py-2.5 no-underline transition hover:bg-gray-950/5 focus-visible:bg-gray-950/5 focus-visible:outline-hidden dark:border-white/10 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
            >
                {{
                    \Filament\Support\generate_icon_html(
                        Heroicon::Plus,
                        attributes: new ComponentAttributeBag([
                            'class' => 'fi-tenant-menu-create-icon mt-px size-5 shrink-0 rounded-md bg-gray-950/5 p-0.5 text-gray-700 dark:bg-white/10 dark:text-gray-200',
                        ]),
                    )
                }}

                <span class="fi-tenant-menu-create-text flex min-w-0 flex-col gap-px leading-tight">
                    <span class="fi-tenant-menu-create-title text-sm font-medium text-gray-950 dark:text-white">{{ $create['title'] }}</span>
                    @if (! empty($create['subtitle']))
                        <span class="fi-tenant-menu-create-subtitle text-xs text-gray-500">{{ $create['subtitle'] }}</span>
                    @endif
                </span>
            </a>
        @endif
    </div>
</x-filament::dropdown>

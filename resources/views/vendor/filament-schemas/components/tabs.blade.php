@php
    use Filament\Schemas\Components\Tabs\Tab;
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\Support\Js;

    $livewireProperty = $getLivewireProperty();
    $isContained = $isContained();
    $isVertical = $isVertical();
    $label = $getLabel();

    $outerAttributes = $attributes
        ->merge([
            'id' => $getId(),
            'wire:key' => $getLivewireKey() . '.container',
        ], escape: false)
        ->merge($getExtraAttributes(), escape: false)
        ->class([
            'fi-sc-tabs',
            'fi-contained' => $isContained,
            'fi-vertical' => $isVertical,
        ]);
@endphp

@if (filled($livewireProperty))
    @php
        $livewire = $getLivewire();
        $activeTab = strval($livewire->{$livewireProperty});
        $canGenerateTabLabel = method_exists($livewire, 'generateTabLabel');

        $tabs = array_filter(
            $getChildSchema()->getComponents(withOriginalKeys: true),
            static fn ($component): bool => $component instanceof Tab,
        );
    @endphp

    <div {{ $outerAttributes }}>
        <nav
            @if (filled($label))
                aria-label="{{ $label }}"
            @endif
            role="tablist"
            @class([
                'fi-tabs m-0 overflow-x-auto rounded-none bg-transparent p-0 shadow-none ring-0 drop-shadow-none [scrollbar-color:var(--gray-300)_transparent] [scrollbar-width:thin] dark:[scrollbar-color:var(--gray-700)_transparent]',
                'fi-contained' => $isContained,
                'fi-vertical' => $isVertical,
            ])
        >
            @foreach ($tabs as $tabKey => $tab)
                @php
                    $tabKey = strval($tabKey);
                    $isActive = $activeTab === $tabKey;
                    $tabIcon = $tab->getIcon();
                    $tabIconPosition = $tab->getIconPosition();
                    $tabLabel = $tab->getLabel() ?? ($canGenerateTabLabel ? $livewire->generateTabLabel($tabKey) : null);
                    $tabBadge = $tab->getBadge();
                @endphp

                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    wire:click="$set('{{ $livewireProperty }}', {{ filled($tabKey) ? Js::from($tabKey) : 'null' }})"
                    wire:loading.attr="disabled"
                    {{
                        $tab
                            ->getExtraAttributeBag()
                            ->class([
                                'fi-tabs-item rounded-none border-b-2 bg-transparent! px-4 p-2 text-sm font-medium shadow-none ring-0 transition-colors',
                                'fi-active' => $isActive,
                                $isActive
                                    ? 'border-b-gray-950 text-gray-950! dark:border-b-white dark:text-white!'
                                    : 'border-b-transparent text-gray-500! hover:border-b-gray-300 hover:text-gray-950! dark:text-gray-400! dark:hover:border-b-gray-600 dark:hover:text-white!',
                            ])
                    }}
                >
                    @if ($tabIcon && $tabIconPosition === IconPosition::Before)
                        {{ \Filament\Support\generate_icon_html($tabIcon) }}
                    @endif

                    <span class="fi-tabs-item-label">
                        {{ $tabLabel }}
                    </span>

                    @if ($tabIcon && $tabIconPosition === IconPosition::After)
                        {{ \Filament\Support\generate_icon_html($tabIcon) }}
                    @endif

                    @if (filled($tabBadge))
                        <x-filament::badge
                            :color="$tab->getBadgeColor($tabBadge)"
                            :icon="$tab->getBadgeIcon($tabBadge)"
                            :icon-position="$tab->getBadgeIconPosition($tabBadge)"
                            size="sm"
                            :tooltip="$tab->getBadgeTooltip($tabBadge)"
                        >
                            {{ $tabBadge }}
                        </x-filament::badge>
                    @endif
                </button>
            @endforeach
        </nav>

        @foreach ($tabs as $tab)
            {{ $tab }}
        @endforeach
    </div>
@else
    @php
        $id = $getId();
        $isTabPersisted = $schemaComponent->isTabPersisted();

        $tabs = array_values(array_filter(
            $getChildSchema()->getComponents(),
            static fn ($component): bool => $component instanceof Tab,
        ));

        $visibleTabKeys = collect($tabs)
            ->filter(static fn (Tab $tab): bool => $tab->isVisible())
            ->map(static fn (Tab $tab) => $tab->getKey(isAbsolute: false))
            ->values()
            ->toJson();
    @endphp

    <div
        x-data="tabsSchemaComponent({
            activeTab: {{ Js::from($getActiveTab()) }},
            isScrollable: {{ Js::from($isScrollable()) }},
            isTabPersisted: {{ Js::from($isTabPersisted) }},
            isTabPersistedInQueryString: {{ Js::from($isTabPersistedInQueryString()) }},
            livewireId: {{ Js::from($getLivewire()->getId()) }},
            schemaKey: {{ Js::from($schemaComponent->getRootContainer()->getKey()) }},
            tab: @if ($isTabPersisted && filled($id)) $persist(null).as({{ Js::from($id) }}) @else {{ Js::from(null) }} @endif,
            tabQueryStringKey: {{ Js::from($getTabQueryStringKey()) }},
        })"
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('tabs', 'filament/schemas') }}"
        wire:ignore.self
        {{ $outerAttributes }}
    >
        <input type="hidden" value="{{ $visibleTabKeys }}" x-ref="tabsData" />

        <nav
            @if (filled($label))
                aria-label="{{ $label }}"
            @endif
            role="tablist"
            @class([
                'fi-tabs m-0 gap-6 overflow-x-auto rounded-none bg-transparent p-0 shadow-none ring-0 drop-shadow-none [scrollbar-color:var(--gray-300)_transparent] [scrollbar-width:thin] dark:[scrollbar-color:var(--gray-700)_transparent]',
                'fi-contained' => $isContained,
                'fi-vertical' => $isVertical,
            ])
        >
            @foreach ($tabs as $tab)
                @php
                    $tabKey = $tab->getKey(isAbsolute: false);
                    $tabIcon = $tab->getIcon();
                    $tabIconPosition = $tab->getIconPosition();
                    $tabBadge = $tab->getBadge();
                @endphp

                <button
                    type="button"
                    role="tab"
                    aria-selected="false"
                    data-tab-key="{{ $tabKey }}"
                    x-bind:aria-selected="tab === {{ Js::from($tabKey) }}"
                    x-on:click="tab = {{ Js::from($tabKey) }}"
                    x-bind:class="{
                        'fi-active border-b-gray-950 text-gray-950 dark:border-b-white dark:text-white': tab === {{ Js::from($tabKey) }},
                        'border-b-transparent text-gray-500 hover:border-b-gray-300 hover:text-gray-950 dark:text-gray-400 dark:hover:border-b-gray-600 dark:hover:text-white': tab !== {{ Js::from($tabKey) }},
                    }"
                    {{ $tab->getExtraAttributeBag()->class(['fi-tabs-item rounded-none border-b-2 bg-transparent px-0 py-2 text-sm font-medium shadow-none ring-0 transition-colors hover:bg-transparent']) }}
                >
                    @if ($tabIcon && $tabIconPosition === IconPosition::Before)
                        {{ \Filament\Support\generate_icon_html($tabIcon) }}
                    @endif

                    <span class="fi-tabs-item-label">
                        {{ $tab->getLabel() }}
                    </span>

                    @if ($tabIcon && $tabIconPosition === IconPosition::After)
                        {{ \Filament\Support\generate_icon_html($tabIcon) }}
                    @endif

                    @if (filled($tabBadge))
                        <x-filament::badge
                            :color="$tab->getBadgeColor($tabBadge)"
                            :icon="$tab->getBadgeIcon($tabBadge)"
                            :icon-position="$tab->getBadgeIconPosition($tabBadge)"
                            size="sm"
                            :tooltip="$tab->getBadgeTooltip($tabBadge)"
                        >
                            {{ $tabBadge }}
                        </x-filament::badge>
                    @endif
                </button>
            @endforeach
        </nav>

        @foreach ($tabs as $tab)
            {{ $tab }}
        @endforeach
    </div>
@endif

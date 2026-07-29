<div
    @if ($isSidebarFullyCollapsibleOnDesktop)
        x-data="{}"
        x-bind:class="{ 'lg:hidden': $store.sidebar.isOpen }"
    @endif
    @class([
        'fi-layout-sidebar-toggle-btn-ctn inline-flex gap-4',
        'lg:hidden' => ! $isSidebarFullyCollapsibleOnDesktop,
    ])
>
    <x-filament::icon-button
        color="gray"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
        :icon-alias="\Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON"
        icon-size="lg"
        :label="__('filament-panels::layout.actions.sidebar.expand.label')"
        x-cloak
        x-data="{}"
        x-on:click="$store.sidebar.open()"
        class="fi-layout-sidebar-toggle-btn"
    />

    <span
        aria-hidden="true"
        class="fi-content-header-divider inline-block h-5 w-px bg-gray-950/10 dark:bg-white/12"
    ></span>
</div>

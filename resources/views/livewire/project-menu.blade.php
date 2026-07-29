<div class="flex items-center gap-6 pt-4 ps-4 lg:pt-8 lg:ps-8 m-auto w-7xl">
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
    
    <x-menu-dropdown
        extra-class="fi-project-menu"
        menu-type="project"
        :wide="false"
        :tooltip="$currentProject?->name ?? __('menu.project.open')"
        :avatar="$currentProject ?? $organization"
        :label="__('menu.project.label')"
        :name="$currentProject?->name ?? __('menu.project.select')"
        :items="$items->all()"
        :search-placeholder="__('menu.project.search')"
        :empty-message="__('menu.project.empty')"
        :create="$createUrl ? [
            'url' => $createUrl,
            'title' => __('menu.project.create'),
            'subtitle' => __('menu.project.create_subtitle'),
        ] : null"
    />
</div>

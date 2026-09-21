@php
    $page = $livewire ?? $this ?? null;

    $title = method_exists($page, 'getHeading') && filled($page->getHeading())
        ? $page->getHeading()
        : (method_exists($page, 'getTitle') ? $page->getTitle() : '');

    $breadcrumbs = method_exists($page, 'getBreadcrumbs') ? $page->getBreadcrumbs() : [];

    $actions = method_exists($page, 'getCachedHeaderActions') ? $page->getCachedHeaderActions() : [];

    $panelId = filament()->getCurrentPanel()?->getId();
    $showSelector = in_array($panelId, ['organization', 'project'], true);
@endphp

<header class="fi-era-topbar sticky top-0 z-20 flex flex-col border-b border-gray-950/5 bg-gray-50 dark:border-white/10 dark:bg-gray-950">
    <div class="flex h-14 items-center gap-3 px-4 md:hidden">
        @livewire('sidebar-toggle')

        @if ($showSelector)
            <div class="min-w-0 flex-1 [&_.fi-tenant-menu-trigger]:w-full [&_.fi-project-menu]:w-full">
                @livewire('project-menu-inline')
            </div>
        @endif
    </div>

    <div class="grid h-13 grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-4 md:h-16 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:px-6 lg:px-8">
        <div class="hidden min-w-0 items-center gap-3 justify-self-start md:flex">
            @livewire('sidebar-toggle')

            @if ($showSelector)
                @livewire('project-menu-inline')
            @endif
        </div>

        <x-era.topbar.breadcrumbs :title="$title" :breadcrumbs="$breadcrumbs" class="justify-self-start md:justify-self-center" />

        <x-era.topbar.actions :actions="$actions" class="justify-self-end" />
    </div>
</header>

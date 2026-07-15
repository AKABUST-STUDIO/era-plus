@php
    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
@endphp

<button class="fi-sidebar-database-notifications-btn">
    {{ \Filament\Support\generate_icon_html('lucide-bell', alias: \Filament\View\PanelsIconAlias::SIDEBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON, size: \Filament\Support\Enums\IconSize::Medium) }}

    <!-- Overwrite: removed label -->

    @if ($unreadNotificationsCount)
        <span
            @if ($isSidebarCollapsibleOnDesktop)
                x-show="$store.sidebar.isOpen"
                x-transition:enter="fi-transition-enter"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:enter-end="fi-transition-enter-end"
            @endif
            class="fi-sidebar-database-notifications-btn-badge-ctn"
        >
            <x-filament::badge>
                {{ $unreadNotificationsCount }}
            </x-filament::badge>
        </span>
    @endif
</button>

@php
    $isSidebarFullyCollapsibleOnDesktop = Filament\Facades\Filament::isSidebarFullyCollapsibleOnDesktop();
@endphp

<div 

@if ($isSidebarFullyCollapsibleOnDesktop)
    x-data="{}"
    x-bind:class="{ 'lg:hidden': $store.sidebar.isOpen }"
@endif
@class([
    'flex items-center gap-6 pt-4 ps-4 lg:pt-8 lg:ps-8 m-auto w-7xl',
    'lg:hidden' => ! $isSidebarFullyCollapsibleOnDesktop
])>
    @livewire('sidebar-toggle')
</div>

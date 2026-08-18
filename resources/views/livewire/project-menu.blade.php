<div class="flex items-center gap-6 pt-4 ps-4 lg:pt-8 lg:ps-8 m-auto w-7xl">
    @livewire('sidebar-toggle')
    
    <div class="flex items-center gap-4 group">
        <x-menu-dropdown
            extra-class="fi-project-menu"
            :wide="false"
            :tooltip="$currentProject?->name ?? __('menu.project.open')"
            :avatar="$currentProject"
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

        @if ($currentProject)
            {!!
                \Filament\Actions\Action::make('clearProject')
                    ->label(__('menu.project.clear'))
                    ->icon(\Filament\Support\Icons\Heroicon::XMark)
                    ->iconButton()
                    ->color('gray')
                    ->url($clearUrl)
                    ->extraAttributes([
                        'class' => 'fi-project-menu-clear opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto focus-visible:opacity-100 focus-visible:pointer-events-auto transition-opacity duration-150',
                        'wire:navigate' => true,
                    ])
                    ->toHtml()
            !!}
        @endif
    </div>
</div>

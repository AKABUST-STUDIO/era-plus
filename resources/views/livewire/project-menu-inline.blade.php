<div class="w-72 rounded-lg border border-gray-950/10 bg-white px-3 py-2 shadow-xs dark:border-white/10 dark:bg-white/5">
    <x-menu-dropdown
        extra-class="fi-project-menu m-0!"
        :tooltip="__('menu.project.open')"
        :avatar="$organization"
        :label="__('menu.project.label')"
        :name="__('menu.project.select')"
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

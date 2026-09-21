<x-menu-dropdown
    extra-class="fi-project-menu m-0!"
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

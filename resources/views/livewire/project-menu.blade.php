<x-menu-dropdown
    extra-class="fi-project-menu"
    :tooltip="$currentProject?->name ?? __('menu.project.open')"
    :avatar="$currentProject ?? $organization"
    :label="__('menu.project.label')"
    :name="$currentProject?->name ?? __('menu.project.select')"
    :groups="[
        $items->reject(fn ($item) => $item['isCurrent']),
        $createUrl ? [['url' => $createUrl, 'name' => __('menu.project.create'), 'icon' => 'lucide-plus']] : [],
    ]"
/>

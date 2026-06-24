<x-menu-dropdown
    extra-class="fi-project-menu"
    :tooltip="__('menu.project.open')"
    :avatar="$organization"
    :label="__('menu.project.open')"
    :name="__('menu.project.select')"
    :groups="[
        $items,
        $createUrl ? [['url' => $createUrl, 'name' => __('menu.project.create'), 'icon' => 'lucide-plus']] : [],
    ]"
/>

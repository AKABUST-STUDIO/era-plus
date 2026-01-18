<x-menu-dropdown
    extra-class="fi-organization-menu"
    :tooltip="$currentOrganization?->name"
    :avatar="$currentOrganization"
    :label="__('menu.organization.label')"
    :name="$currentOrganization?->name ?? '—'"
    :groups="[$items->reject(fn ($item) => $item['isCurrent'])]"
/>

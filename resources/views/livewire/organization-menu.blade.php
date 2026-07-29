<div>
    <x-menu-dropdown
        extra-class="fi-organization-menu"
        menu-type="organization"
        :tooltip="$currentOrganization?->name"
        :avatar="$currentOrganization"
        :label="__('menu.organization.label')"
        :name="$currentOrganization?->name ?? '—'"
        :badge="$currentOrganization?->subscription_tier->getLabel()"
        :items="$items->all()"
        :search-placeholder="__('menu.organization.search')"
        :empty-message="__('menu.organization.empty')"
        :create="$createUrl ? [
            'url' => $createUrl,
            'title' => __('menu.organization.create'),
            'subtitle' => __('menu.organization.create_subtitle'),
        ] : null"
    />
</div>

@php
    $create = $canCreate ? [
        'click' => "mountAction('createOrganization')",
        'title' => __('menu.organization.create'),
        'subtitle' => __('menu.organization.create_subtitle'),
    ] : null;
@endphp

<div>
    <x-menu-dropdown
        extra-class="fi-organization-menu"
        :tooltip="$currentOrganization?->name"
        :avatar="$currentOrganization"
        :label="__('menu.organization.label')"
        :name="$currentOrganization?->name ?? '—'"
        :url="$organizationUrl"
        :toggle-label="__('menu.organization.select')"
        :items="$items->all()"
        :search-placeholder="__('menu.organization.search')"
        :empty-message="__('menu.organization.empty')"
        :create="$create"
    />

    <x-filament-actions::modals />
</div>

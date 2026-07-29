<?php

namespace App\Livewire;

use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\User;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrganizationMenu extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $currentOrganization = OrganizationService::current();

        if (! $user instanceof User || ! $currentOrganization instanceof Organization) {
            return view('livewire.organization-menu', [
                'currentOrganization' => null,
                'items' => collect(),
                'createUrl' => null,
            ]);
        }

        $items = OrganizationService::organizationsFor($user)->map(fn (Organization $organization) => [
            'name' => $organization->name,
            'slug' => $organization->slug,
            'url' => OrganizationService::urlFor($organization),
            'image' => $organization->getAvatarUrl(),
            'badge' => $organization->subscription_tier->getLabel(),
            'isCurrent' => $organization->is($currentOrganization),
        ]);

        return view('livewire.organization-menu', [
            'currentOrganization' => $currentOrganization,
            'items' => $items,
            'createUrl' => Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getTenantRegistrationUrl(),
        ]);
    }
}

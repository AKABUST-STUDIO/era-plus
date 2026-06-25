<?php

namespace App\Livewire;

use App\Facades\OrganizationService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UserFooter extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $organization = OrganizationService::current();

        return view('livewire.user-footer', [
            'user' => $user instanceof User ? $user : null,
            'accountUrl' => route('filament.user.home'),
            'showUpgrade' => OrganizationService::shouldShowUpgradeCta(),
            'upgradeUrl' => $organization
                ? route('filament.organization-settings.pages.billing', ['organization' => $organization->slug])
                : null,
            'logoutFormAction' => filament()->getCurrentOrDefaultPanel()?->getLogoutUrl(),
        ]);
    }
}

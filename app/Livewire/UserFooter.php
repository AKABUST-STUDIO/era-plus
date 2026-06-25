<?php

namespace App\Livewire;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Billing;
use App\Filament\User\Pages\Settings;
use App\Models\User;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use App\Providers\Filament\UserPanelProvider;
use Filament\Facades\Filament;
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
            'accountUrl' => Settings::getUrl(panel: UserPanelProvider::PANEL_ID),
            'showUpgrade' => OrganizationService::shouldShowUpgradeCta(),
            'upgradeUrl' => $organization
                ? Billing::getUrl(parameters: ['organization' => $organization], tenant: $organization, panel: SettingsPanelProvider::PANEL_ID)
                : null,
            'logoutFormAction' => Filament::getCurrentOrDefaultPanel()?->getLogoutUrl(),
        ]);
    }
}

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
            'avatarUrl' => $this->avatarUrl($user),
            'accountUrl' => $this->safeRoute('filament.user.pages.dashboard') ?? '#',
            'showUpgrade' => OrganizationService::shouldShowUpgradeCta(),
            'upgradeUrl' => $organization
                ? $this->safeRoute('filament.organization-settings.pages.billing', ['organization' => $organization->slug])
                : null,
            'logoutFormAction' => $this->safeLogoutUrl(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function safeRoute(string $name, array $parameters = []): ?string
    {
        try {
            return route($name, $parameters);
        } catch (\Throwable) {
            return null;
        }
    }

    private function safeLogoutUrl(): ?string
    {
        try {
            return filament()->getCurrentOrDefaultPanel()?->getLogoutUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    private function avatarUrl(?User $user): string
    {
        if ($user === null) {
            return 'https://ui-avatars.com/api/?name=?&size=128';
        }

        return $user->getFilamentAvatarUrl()
            ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&size=128';
    }
}

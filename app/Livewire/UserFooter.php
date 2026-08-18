<?php

namespace App\Livewire;

use App\Filament\User\Pages\Settings;
use App\Models\User;
use App\Providers\Filament\UserPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UserFooter extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.user-footer', [
            'user' => $user instanceof User ? $user : null,
            'accountUrl' => Settings::getUrl(panel: UserPanelProvider::PANEL_ID),
            'logoutFormAction' => Filament::getCurrentOrDefaultPanel()?->getLogoutUrl(),
        ]);
    }
}

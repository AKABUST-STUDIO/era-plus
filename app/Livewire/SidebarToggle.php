<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SidebarToggle extends Component
{
    public function render(): View
    {
        $isSidebarFullyCollapsibleOnDesktop = Filament::isSidebarFullyCollapsibleOnDesktop();

        return view('livewire.sidebar-toggle', [
            'isSidebarFullyCollapsibleOnDesktop' => $isSidebarFullyCollapsibleOnDesktop,
        ]);
    }
}

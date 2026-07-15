<?php

namespace App\Filament\User\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Passkey;

/**
 * @property-read Collection<int, Passkey> $passkeys
 */
class Authentication extends Page
{
    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.user.pages.authentication';

    public function getTitle(): string
    {
        return 'Authentication';
    }

    /**
     * @return Collection<int, Passkey>
     */
    public function getPasskeysProperty(): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        return $user->passkeys()->latest()->get();
    }

    public function deletePasskey(int $id): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $user->passkeys()->whereKey($id)->delete();

        Notification::make()->title('Passkey removed.')->success()->send();
    }
}

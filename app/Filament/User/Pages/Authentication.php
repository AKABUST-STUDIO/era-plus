<?php

namespace App\Filament\User\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laragear\WebAuthn\Models\WebAuthnCredential;

/**
 * @property-read Collection<int, WebAuthnCredential> $passkeys
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
     * @return Collection<int, WebAuthnCredential>
     */
    public function getPasskeysProperty()
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        return $user->webAuthnCredentials()
            ->orderByDesc('created_at')
            ->get();
    }

    public function deletePasskey(string $id): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $user->webAuthnCredentials()->whereKey($id)->delete();

        Notification::make()->title('Passkey removed.')->success()->send();
    }
}

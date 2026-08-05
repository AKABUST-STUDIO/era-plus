<?php

namespace App\Http\Controllers\Settings;

use App\Filament\User\Pages\Settings;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfirmEmailChangeController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->getKey() === $user->getKey(), 403);

        $newEmail = mb_strtolower(trim((string) $request->query('email')));

        $taken = User::query()
            ->where('email', $newEmail)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($taken) {
            Notification::make()
                ->title(__('notifications.email_change_taken'))
                ->danger()
                ->send();

            return redirect()->to(Settings::getUrl(panel: 'user'));
        }

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => now(),
        ])->save();

        Notification::make()
            ->title(__('notifications.email_change_confirmed'))
            ->success()
            ->send();

        return redirect()->to(Settings::getUrl(panel: 'user'));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->to(Filament::getLoginUrl())
                ->withErrors(['email' => 'Sign-in link is invalid or has expired.']);
        }

        $email = mb_strtolower(trim((string) $request->query('email', '')));
        $code = trim((string) $request->query('code', ''));

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return redirect()->to(Filament::getRegistrationUrl().'?email='.urlencode($email));
        }

        $result = $user->attemptLoginUsingOneTimePassword($code, remember: true);

        if (! $result->isOk()) {
            return redirect()->to(Filament::getLoginUrl().'?email='.urlencode($email))
                ->withErrors(['email' => $result->validationMessage()]);
        }

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
        }

        $request->session()->regenerate();

        return redirect()->intended(Filament::getUrl());
    }
}

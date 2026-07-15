<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginCodeService;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MagicLinkController extends Controller
{
    public function __invoke(Request $request, LoginCodeService $codes): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->to(Filament::getLoginUrl())
                ->withErrors(['email' => 'Sign-in link is invalid or has expired.']);
        }

        $email = mb_strtolower(trim((string) $request->query('email', '')));
        $code = trim((string) $request->query('code', ''));

        if (! $codes->verify($email, $code)) {
            return redirect()->to(Filament::getLoginUrl().'?email='.urlencode($email))
                ->withErrors(['email' => 'Sign-in link is invalid or has expired.']);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return redirect()->to(Filament::getRegistrationUrl().'?email='.urlencode($email));
        }

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(Filament::getUrl());
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Facades\AuthenticationService;
use App\Http\Controllers\Controller;
use App\Models\OAuthAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialiteController extends Controller
{
    private const PROVIDERS = ['google', 'microsoft', 'apple'];

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $socialUser = Socialite::driver($provider)->user();
        $email = mb_strtolower(trim((string) $socialUser->getEmail()));

        if ($email === '') {
            return redirect()->to(Filament::getLoginUrl())
                ->withErrors(['email' => 'Your '.$provider.' account did not share an email address.']);
        }

        $user = DB::transaction(fn () => $this->findOrCreateUser($provider, $socialUser, $email));

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(Filament::getUrl());
    }

    private function findOrCreateUser(string $provider, SocialiteUser $socialUser, string $email): User
    {
        $account = OAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', (string) $socialUser->getId())
            ->first();

        if ($account) {
            return $account->user;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = AuthenticationService::createUser($email, $socialUser->getName() ?: null);
        }

        OAuthAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => (string) $socialUser->getId(),
            'email' => $email,
        ]);

        return $user;
    }
}

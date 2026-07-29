<?php

namespace App\Services;

use App\Mail\MissingAccountSignInAttempt;
use App\Models\User;
use App\Support\EmailUsername;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function login(string $email): void
    {
        try {
            User::query()->where('email', $email)->firstOrFail()->sendOneTimePassword();
        } catch (ModelNotFoundException $_) {
            Mail::to($email)->queue(new MissingAccountSignInAttempt($email));
        }
    }

    public function register(string $email): void
    {
        try {
            User::query()->where('email', $email)->firstOrFail()->sendAccountAlreadyExistsMailable();
        } catch (ModelNotFoundException $_) {
            $name = EmailUsername::toDisplayName($email);
            $uuid = (string) Str::uuid();
            $slug = (Str::slug($name) ?: 'user').'-'.Str::substr($uuid, 0, 8);

            $user = User::query()->create([
                'uuid' => $uuid,
                'slug' => $slug,
                'username' => $slug,
                'email' => $email,
                'name' => $name,
                'password' => Str::random(64),
            ]);

            $user->sendInitialRegistrationMailable();
        }
    }

    public function authenticate(string $email, string $code, bool $shouldSendWelcomeMailable = false): void
    {
        try {
            $user = User::query()->where('email', $email)->firstOrFail();
        } catch (ModelNotFoundException $_) {
            throw ValidationException::withMessages([
                'data.code' => __('filament-panels::auth/pages/login.messages.account_missing'),
            ]);
        }

        $result = $user->attemptLoginUsingOneTimePassword($code, remember: true);

        if (! $result->isOk()) {
            throw ValidationException::withMessages([
                'data.code' => $result->validationMessage(),
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();

            if ($shouldSendWelcomeMailable) {
                $user->sendWelcomeMailable();
            }
        }

        session()->regenerate();
    }
}

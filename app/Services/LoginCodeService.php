<?php

namespace App\Services;

use App\Mail\LoginCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LoginCodeService
{
    private const CODE_TTL_MINUTES = 10;

    private const MAX_REQUESTS_PER_HOUR = 5;

    private const MAX_VERIFICATIONS_PER_CODE = 5;

    public function issueAndSend(string $email): void
    {
        $email = mb_strtolower(trim($email));

        RateLimiter::hit($this->requestKey($email), 3600);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(
            $this->codeKey($email),
            ['hash' => Hash::make($code), 'attempts' => 0],
            now()->addMinutes(self::CODE_TTL_MINUTES),
        );

        Mail::to($email)->send(new LoginCodeMail(
            code: $code,
            magicLinkUrl: $this->magicLinkUrl($email, $code),
            expiresInMinutes: self::CODE_TTL_MINUTES,
        ));
    }

    public function verify(string $email, string $code): bool
    {
        $email = mb_strtolower(trim($email));
        $key = $this->codeKey($email);

        $entry = Cache::get($key);

        if (! is_array($entry) || ! isset($entry['hash'], $entry['attempts'])) {
            return false;
        }

        if ($entry['attempts'] >= self::MAX_VERIFICATIONS_PER_CODE) {
            Cache::forget($key);

            return false;
        }

        if (! Hash::check($code, $entry['hash'])) {
            Cache::put($key, [
                'hash' => $entry['hash'],
                'attempts' => $entry['attempts'] + 1,
            ], now()->addMinutes(self::CODE_TTL_MINUTES));

            return false;
        }

        Cache::forget($key);

        return true;
    }

    public function tooManyRequests(string $email): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->requestKey(mb_strtolower(trim($email))),
            self::MAX_REQUESTS_PER_HOUR,
        );
    }

    public function secondsUntilRetry(string $email): int
    {
        return RateLimiter::availableIn(
            $this->requestKey(mb_strtolower(trim($email))),
        );
    }

    public function magicLinkUrl(string $email, string $code): string
    {
        return URL::temporarySignedRoute(
            'auth.magic-link',
            now()->addMinutes(self::CODE_TTL_MINUTES),
            ['email' => $email, 'code' => $code],
        );
    }

    public static function deriveNameFromEmail(string $email): string
    {
        $username = Str::before($email, '@');
        $normalized = preg_replace('/[._\-+]+/', ' ', $username) ?? $username;

        return Str::of($normalized)->squish()->title()->value();
    }

    private function codeKey(string $email): string
    {
        return 'login-code:'.hash('sha256', $email);
    }

    private function requestKey(string $email): string
    {
        return 'login-code-requests:'.hash('sha256', $email);
    }
}

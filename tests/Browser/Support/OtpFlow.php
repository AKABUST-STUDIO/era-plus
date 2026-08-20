<?php

namespace Tests\Browser\Support;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

class OtpFlow
{
    public static function loginAs(Browser $browser, string $email): Browser
    {
        $browser->visit('/login')
            ->waitFor('input[type="email"]')
            ->type('input[type="email"]', $email)
            ->press('Continue with email')
            ->waitFor('input.fi-otp-input', 10);

        $browser->waitUsing(2, 100, function () use ($browser): bool {
            $count = (int) $browser->script('return document.querySelectorAll("input.fi-otp-input").length;')[0];

            return $count >= 6;
        });

        // Wait up to 5s for the OTP row to exist in the DB (Livewire creates it async on submit).
        $code = null;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            try {
                $code = self::latestCodeFor($email);
                break;
            } catch (\RuntimeException) {
                usleep(100_000);
            }
        }

        if ($code === null) {
            throw new \RuntimeException("Timed out waiting for OTP row for {$email}");
        }

        $browser->click('input.fi-otp-input:first-child');

        foreach (str_split($code) as $digit) {
            $browser->keys('body', $digit);
            usleep(80_000);
        }

        return $browser->waitUsing(30, 500, function () use ($browser): bool {
            return ! str_contains($browser->driver->getCurrentURL(), '/login');
        });
    }

    private static function latestCodeFor(string $email): string
    {
        $row = DB::table('one_time_passwords as otp')
            ->join('users', 'users.id', '=', 'otp.authenticatable_id')
            ->where('users.email', $email)
            ->orderByDesc('otp.id')
            ->first();

        if ($row === null) {
            throw new \RuntimeException("No OTP found for {$email}");
        }

        return (string) $row->password;
    }
}

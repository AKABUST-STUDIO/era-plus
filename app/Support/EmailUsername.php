<?php

namespace App\Support;

use Illuminate\Support\Str;

class EmailUsername
{
    public static function toDisplayName(string $email): string
    {
        $username = Str::before($email, '@');
        $normalized = preg_replace('/[._\-+]+/', ' ', $username) ?? $username;

        return Str::of($normalized)->squish()->title()->value();
    }
}

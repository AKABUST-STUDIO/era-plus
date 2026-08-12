<?php

declare(strict_types=1);

namespace App\Support;

class GoogleCalendarCredentials
{
    public static function path(): string
    {
        return storage_path('app/google-calendar/service-account-credentials.json');
    }

    public static function isConfigured(): bool
    {
        return env('GOOGLE_CALENDAR_CREDENTIALS_B64', '') !== '';
    }

    public static function impersonate(): ?string
    {
        $email = (string) env('GOOGLE_CALENDAR_IMPERSONATE', '');

        return $email !== '' ? $email : null;
    }

    public static function materialize(): void
    {
        $encoded = (string) env('GOOGLE_CALENDAR_CREDENTIALS_B64', '');

        if ($encoded === '') {
            return;
        }

        $path = self::path();

        if (is_file($path)) {
            return;
        }

        $decoded = base64_decode($encoded, true);

        if ($decoded === false) {
            return;
        }

        $directory = dirname($path);

        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        file_put_contents($path, $decoded);
    }
}

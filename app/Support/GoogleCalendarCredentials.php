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
        return (string) config('services.google.credentials_b64') !== '';
    }

    public static function impersonate(): ?string
    {
        $email = (string) config('services.google.impersonate');

        return $email !== '' ? $email : null;
    }

    public static function materialize(): void
    {
        $encoded = (string) config('services.google.credentials_b64');

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

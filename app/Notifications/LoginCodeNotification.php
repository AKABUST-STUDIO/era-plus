<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification;

class LoginCodeNotification extends OneTimePasswordNotification implements ShouldQueue
{
    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return ['mail' => 'email'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->oneTimePassword->expires_at;
        $code = $this->oneTimePassword->password;

        $magicLinkUrl = URL::temporarySignedRoute(
            'auth.magic-link',
            $expiresAt,
            ['email' => $notifiable->email, 'code' => $code],
        );

        return (new MailMessage)
            ->subject('Your sign-in code: '.$code)
            ->markdown('emails.login-code', [
                'code' => $code,
                'magicLinkUrl' => $magicLinkUrl,
                'expiresInMinutes' => (int) now()->diffInMinutes($expiresAt),
            ]);
    }
}

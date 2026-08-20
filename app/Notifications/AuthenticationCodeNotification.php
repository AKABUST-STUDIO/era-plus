<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification;

abstract class AuthenticationCodeNotification extends OneTimePasswordNotification implements ShouldQueue
{
    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return ['mail' => config('queue.names.email')];
    }

    abstract protected function markdownView(): string;

    /**
     * @return array<string, mixed>
     */
    protected function extraViewData(): array
    {
        return [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->oneTimePassword->expires_at;
        $code = $this->oneTimePassword->password;

        return (new MailMessage)
            ->subject($this->subject())
            ->markdown($this->markdownView(), [
                'appName' => str(config('app.name'))->ucfirst(),
                'email' => $notifiable->email,
                'code' => $code,
                'magicLinkUrl' => URL::temporarySignedRoute(
                    'auth.magic-link',
                    $expiresAt,
                    ['email' => $notifiable->email, 'code' => $code],
                ),
                'expiresInMinutes' => (int) now()->diffInMinutes($expiresAt),
                ...$this->extraViewData(),
            ]);
    }
}

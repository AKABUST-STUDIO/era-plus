<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeConfirmation extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $newEmail,
        public string $confirmUrl,
        public int $expiresInMinutes,
    ) {
        $this->onQueue(config('queue.names.email'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.email_change_confirm.subject', [
                'app' => str(config('app.name'))->ucfirst(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-change-confirmation',
            with: [
                'appName' => str(config('app.name'))->ucfirst(),
                'oldEmail' => $this->user->email,
                'newEmail' => $this->newEmail,
                'confirmUrl' => $this->confirmUrl,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}

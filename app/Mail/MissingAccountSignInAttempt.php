<?php

namespace App\Mail;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MissingAccountSignInAttempt extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public string $email)
    {
        $this->onQueue(config('queue.names.email'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.missing_account.subject', ['app' => str(config('app.name'))->ucfirst()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.missing-account-sign-in-attempt',
            with: [
                'appName' => str(config('app.name'))->ucfirst(),
                'registerUrl' => Filament::getPanel('organization')->getRegistrationUrl().'?email='.urlencode($this->email),
            ],
        );
    }
}

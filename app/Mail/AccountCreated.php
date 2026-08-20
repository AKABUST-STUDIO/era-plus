<?php

namespace App\Mail;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCreated extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user)
    {
        $this->onQueue(config('queue.names.email'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.welcome.subject', ['app' => str(config('app.name'))->ucfirst()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-created',
            with: [
                'appName' => str(config('app.name'))->ucfirst(),
                'name' => $this->user->name,
                'dashboardUrl' => Filament::getPanel('organization')->getUrl(),
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Filament\User\Pages\Settings;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeRequestedNotice extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $newEmail,
    ) {
        $this->onQueue('email');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.email_change_notice.subject', [
                'app' => str(config('app.name'))->ucfirst(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-change-requested-notice',
            with: [
                'appName' => str(config('app.name'))->ucfirst(),
                'newEmail' => $this->newEmail,
                'securityUrl' => Settings::getUrl(panel: 'user'),
            ],
        );
    }
}

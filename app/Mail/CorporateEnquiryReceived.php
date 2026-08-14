<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorporateEnquiryReceived extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $subject,
        public string $body,
    ) {
        $this->onQueue('email');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.corporate_enquiry_received.subject', [
                'subject' => $this->subject,
            ]),
            replyTo: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.corporate-enquiry-received',
        );
    }
}

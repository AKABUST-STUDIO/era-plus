<?php

namespace App\Mail;

use App\Models\SupportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportRequestReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public SupportRequest $request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your support request: '.$this->request->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-request-received',
            with: [
                'subject' => $this->request->subject,
                'body' => $this->request->body,
                'priority' => ucfirst($this->request->priority),
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackReceived extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Feedback $feedback)
    {
        $this->onQueue(config('queue.names.email'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.feedback_received.subject', [
                'subject' => $this->feedback->subject ?? '—',
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feedback-received',
            with: [
                'feedback' => $this->feedback,
            ],
        );
    }
}

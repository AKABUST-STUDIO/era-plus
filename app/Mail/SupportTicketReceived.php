<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketReceived extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public SupportTicket $ticket)
    {
        $this->onQueue(config('queue.names.email'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.support_ticket_received.subject', [
                'subject' => $this->ticket->subject,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-ticket-received',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }
}

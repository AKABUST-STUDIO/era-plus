<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public string $magicLinkUrl,
        public int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your sign-in code: '.$this->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.login-code',
            with: [
                'code' => $this->code,
                'magicLinkUrl' => $this->magicLinkUrl,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}

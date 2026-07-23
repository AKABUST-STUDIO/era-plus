<?php

namespace App\Mail;

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitation extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $invitee,
        public OrganizationRole $role,
        public ?User $invitedBy = null,
    ) {
        $this->onQueue('email');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.organization_invitation.subject', ['organization' => $this->organization->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.organization-invitation',
            with: [
                'organizationName' => $this->organization->name,
                'roleLabel' => $this->role->getLabel(),
                'inviterName' => $this->invitedBy?->name ?? __('emails.organization_invitation.default_inviter'),
                'signInUrl' => route('filament.organization.auth.login', ['email' => $this->invitee->email]),
            ],
        );
    }
}

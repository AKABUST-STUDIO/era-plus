<?php

namespace App\Notifications;

use App\Enums\Organization\OrganizationRole;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Spatie\OneTimePasswords\Models\OneTimePassword;

class OrganizationInvitationNotification extends AuthenticationCodeNotification
{
    public function __construct(
        OneTimePassword $oneTimePassword,
        public Organization $organization,
        public Role $role,
        public ?User $invitedBy = null,
    ) {
        parent::__construct($oneTimePassword);
    }

    public function subject(): string
    {
        return __('emails.organization_invitation.subject', ['organization' => $this->organization->name]);
    }

    protected function markdownView(): string
    {
        return 'emails.organization-invitation';
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraViewData(): array
    {
        return [
            'organizationName' => $this->organization->name,
            'roleLabel' => OrganizationRole::tryFrom($this->role->name)?->getLabel() ?? $this->role->name,
            'inviterName' => $this->invitedBy?->name ?? __('emails.organization_invitation.default_inviter'),
        ];
    }
}

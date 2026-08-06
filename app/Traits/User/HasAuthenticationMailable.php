<?php

namespace App\Traits\User;

use App\Mail\AccountCreated;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountAlreadyExistsNotification;
use App\Notifications\OrganizationInvitationNotification;
use App\Notifications\RegistrationCodeNotification;
use Illuminate\Support\Facades\Mail;

trait HasAuthenticationMailable
{
    public function sendAccountAlreadyExistsMailable(): self
    {
        $this->notify(new AccountAlreadyExistsNotification($this->createOneTimePassword()));

        return $this;
    }

    public function sendInitialRegistrationMailable(): self
    {
        $this->notify(new RegistrationCodeNotification($this->createOneTimePassword()));

        return $this;
    }

    public function sendOrganizationInvitationMailable(Organization $organization, Role $role, ?User $invitedBy = null): self
    {
        $this->notify(new OrganizationInvitationNotification(
            $this->createOneTimePassword(),
            $organization,
            $role,
            $invitedBy,
        ));

        return $this;
    }

    public function sendWelcomeMailable(): self
    {
        Mail::to($this->email)->queue(new AccountCreated($this));

        return $this;
    }
}

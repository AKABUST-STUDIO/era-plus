<?php

declare(strict_types=1);

namespace App\Policies;

use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectAccess;

class OrganizationPolicy
{
    public function inviteMember(User $user, Organization $organization): bool
    {
        return app(ProjectAccess::class)->administersOrganization($user, $organization);
    }

    public function changeMemberRole(User $user, Organization $organization, User $target): bool
    {
        if (! app(ProjectAccess::class)->administersOrganization($user, $organization)) {
            return false;
        }

        return ! OrganizationService::isSoleAdmin($organization, $target);
    }

    public function removeMember(User $user, Organization $organization, User $target): bool
    {
        if (! app(ProjectAccess::class)->administersOrganization($user, $organization)
            && ! $user->is($target)) {
            return false;
        }

        return ! OrganizationService::isSoleAdmin($organization, $target);
    }
}

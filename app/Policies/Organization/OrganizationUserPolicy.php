<?php

declare(strict_types=1);

namespace App\Policies\Organization;

use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\User;
use App\Services\ProjectAccess;

class OrganizationUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view_any_organization_user', OrganizationService::current());
    }

    public function view(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->allows($user, 'view_organization_user', $organizationUser->organization);
    }

    public function update(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->allows($user, 'update_organization_user', $organizationUser->organization)
            && ! OrganizationService::isSoleAdmin($organizationUser->organization, $organizationUser->user);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, 'update_any_organization_user', OrganizationService::current());
    }

    public function delete(User $user, OrganizationUser $organizationUser): bool
    {
        if (OrganizationService::isSoleAdmin($organizationUser->organization, $organizationUser->user)) {
            return false;
        }

        return $user->is($organizationUser->user)
            || $this->allows($user, 'delete_organization_user', $organizationUser->organization);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, 'delete_any_organization_user', OrganizationService::current());
    }

    private function allows(User $user, string $permission, ?Organization $organization): bool
    {
        return $organization instanceof Organization
            && app(ProjectAccess::class)->organizationAllows($user, $permission, $organization);
    }
}

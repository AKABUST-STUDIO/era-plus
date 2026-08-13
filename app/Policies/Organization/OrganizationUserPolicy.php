<?php

declare(strict_types=1);

namespace App\Policies\Organization;

use App\Enums\Permissions\OrganizationUserPermission;
use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Models\User;
use App\Services\ProjectAccess;

class OrganizationUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, OrganizationUserPermission::ViewAny->value, OrganizationService::current());
    }

    public function view(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->allows($user, OrganizationUserPermission::View->value, $organizationUser->organization);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, OrganizationUserPermission::Create->value, OrganizationService::current());
    }

    public function update(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->allows($user, OrganizationUserPermission::Update->value, $organizationUser->organization)
            && ! OrganizationService::isSoleAdmin($organizationUser->organization, $organizationUser->user);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, OrganizationUserPermission::UpdateAny->value, OrganizationService::current());
    }

    public function delete(User $user, OrganizationUser $organizationUser): bool
    {
        if (OrganizationService::isSoleAdmin($organizationUser->organization, $organizationUser->user)) {
            return false;
        }

        return $user->is($organizationUser->user)
            || $this->allows($user, OrganizationUserPermission::Delete->value, $organizationUser->organization);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, OrganizationUserPermission::DeleteAny->value, OrganizationService::current());
    }

    private function allows(User $user, string $permission, ?Organization $organization): bool
    {
        return $organization instanceof Organization
            && app(ProjectAccess::class)->organizationAllows($user, $permission, $organization);
    }
}

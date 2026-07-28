<?php

namespace App\Services;

use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;

class ProjectAccess
{
    public const ABILITY_MANAGE_MEMBERS = 'project.manage_members';

    public const ABILITY_MANAGE_PARTICIPANTS = 'project.manage_participants';

    public const ABILITY_MANAGE_SETTINGS = 'project.manage_settings';

    public const ABILITY_ADMINISTER_ORGANIZATION = 'organization.administer';

    public const ABILITY_ADMINISTER_PROJECT = 'project.administer';

    public function can(User $user, string $ability, Project $project): bool
    {
        if ($this->administersOrganization($user, $project->organization)) {
            return true;
        }

        $role = $user->roleFor($project);

        if ($role === null) {
            return false;
        }

        if ($this->grants($role, self::ABILITY_ADMINISTER_PROJECT)) {
            return true;
        }

        return $this->grants($role, $ability);
    }

    public function organizationAllows(User $user, string $permission, Organization $organization): bool
    {
        $role = $user->roleFor($organization);

        if ($role === null) {
            return false;
        }

        if ($this->grants($role, self::ABILITY_ADMINISTER_ORGANIZATION)) {
            return true;
        }

        return $this->grants($role, $permission);
    }

    public function currentOrganizationAllows(User $user, string $permission): bool
    {
        $organization = OrganizationService::current();

        return $organization instanceof Organization
            && $this->organizationAllows($user, $permission, $organization);
    }

    public function administersOrganization(User $user, Organization $organization): bool
    {
        $role = $user->roleFor($organization);

        return $role !== null && $this->grants($role, self::ABILITY_ADMINISTER_ORGANIZATION);
    }

    public function administersProject(User $user, Project $project): bool
    {
        $role = $user->roleFor($project);

        return $role !== null && $this->grants($role, self::ABILITY_ADMINISTER_PROJECT);
    }

    private function grants(Role $role, string $permission): bool
    {
        return $role->permissions->contains('name', $permission);
    }
}

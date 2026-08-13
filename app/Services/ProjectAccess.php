<?php

namespace App\Services;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Facades\OrganizationService;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;

class ProjectAccess
{
    public function can(User $user, string $ability, Project $project): bool
    {
        if ($this->administersOrganization($user, $project->organization)) {
            return true;
        }

        $role = $user->roleFor($project);

        return $role !== null && $this->grants($role, $ability);
    }

    public function organizationAllows(User $user, string $permission, Organization $organization): bool
    {
        $role = $user->roleFor($organization);

        return $role !== null && $this->grants($role, $permission);
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

        return $role !== null && $role->name === OrganizationRole::Admin->value;
    }

    public function administersProject(User $user, Project $project): bool
    {
        $role = $user->roleFor($project);

        return $role !== null && $role->name === ProjectRole::Admin->value;
    }

    private function grants(Role $role, string $permission): bool
    {
        return $role->permissions->contains('name', $permission);
    }
}

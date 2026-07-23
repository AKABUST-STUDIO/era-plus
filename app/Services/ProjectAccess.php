<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Project;
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

        if ($role->hasPermissionTo(self::ABILITY_ADMINISTER_PROJECT)) {
            return true;
        }

        return $role->hasPermissionTo($ability);
    }

    public function administersOrganization(User $user, Organization $organization): bool
    {
        if ($organization->owner()?->is($user)) {
            return true;
        }

        $role = $user->roleFor($organization);

        return $role !== null && $role->hasPermissionTo(self::ABILITY_ADMINISTER_ORGANIZATION);
    }

    public function administersProject(User $user, Project $project): bool
    {
        if ($this->administersOrganization($user, $project->organization)) {
            return true;
        }

        $role = $user->roleFor($project);

        return $role !== null && $role->hasPermissionTo(self::ABILITY_ADMINISTER_PROJECT);
    }
}

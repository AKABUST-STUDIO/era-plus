<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions\ProjectPermission;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, ProjectPermission::ViewAny->value);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->projects()->whereKey($project->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, ProjectPermission::Create->value);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->allows($user, ProjectPermission::UpdateAny->value, $project->organization);
    }

    public function updateAny(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, ProjectPermission::UpdateAny->value);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->allows($user, ProjectPermission::DeleteAny->value, $project->organization);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, ProjectPermission::DeleteAny->value);
    }

    private function allows(User $user, string $permission, ?Organization $organization): bool
    {
        return $organization instanceof Organization
            && app(ProjectAccess::class)->organizationAllows($user, $permission, $organization);
    }

    private function allowsInCurrentOrganization(User $user, string $permission): bool
    {
        return app(ProjectAccess::class)->currentOrganizationAllows($user, $permission);
    }
}

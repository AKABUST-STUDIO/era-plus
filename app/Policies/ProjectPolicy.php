<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, 'view_any_project');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->allows($user, 'view_project', $project->organization);
    }

    public function create(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, 'create_project');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->allows($user, 'update_project', $project->organization);
    }

    public function updateAny(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, 'update_any_project');
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->allows($user, 'delete_project', $project->organization);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowsInCurrentOrganization($user, 'delete_any_project');
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

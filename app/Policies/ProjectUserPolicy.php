<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions\ProjectUserPermission;
use App\Facades\ProjectService;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ProjectUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectUserPermission::ViewAny->value);
    }

    public function view(User $user, ProjectUser $projectUser): bool
    {
        return $this->allows($user, ProjectUserPermission::ViewAny->value);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectUserPermission::Create->value);
    }

    public function update(User $user, ProjectUser $projectUser): bool
    {
        return $this->allows($user, ProjectUserPermission::Update->value);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, ProjectUserPermission::UpdateAny->value);
    }

    public function delete(User $user, ProjectUser $projectUser): bool
    {
        return $this->allows($user, ProjectUserPermission::Delete->value);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectUserPermission::DeleteAny->value);
    }

    private function allows(User $user, string $permission): bool
    {
        $project = ProjectService::current();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $permission, $project);
    }
}

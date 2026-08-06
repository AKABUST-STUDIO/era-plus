<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ProjectUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public function view(User $user, ProjectUser $projectUser): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public function update(User $user, ProjectUser $projectUser): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public function delete(User $user, ProjectUser $projectUser): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    private function allows(User $user, string $ability): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $ability, $project);
    }
}

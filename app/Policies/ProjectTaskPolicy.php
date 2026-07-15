<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ProjectTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function view(User $user, ProjectTask $projectTask): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function update(User $user, ProjectTask $projectTask): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function delete(User $user, ProjectTask $projectTask): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function restore(User $user, ProjectTask $projectTask): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function forceDelete(User $user, ProjectTask $projectTask): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    private function allows(User $user, string $ability): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $ability, $project);
    }
}

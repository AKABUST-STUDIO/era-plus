<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ProjectEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function view(User $user, ProjectEvent $projectEvent): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function update(User $user, ProjectEvent $projectEvent): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function delete(User $user, ProjectEvent $projectEvent): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function restore(User $user, ProjectEvent $projectEvent): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public function forceDelete(User $user, ProjectEvent $projectEvent): bool
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

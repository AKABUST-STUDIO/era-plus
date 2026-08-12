<?php

declare(strict_types=1);

namespace App\Policies\Project;

use App\Models\Project;
use App\Models\Project\ProjectEvent;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ProjectEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isProjectMember($user);
    }

    public function view(User $user, ProjectEvent $activity): bool
    {
        return $this->isProjectMember($user);
    }

    public function create(User $user): bool
    {
        return $this->allowsManage($user);
    }

    public function update(User $user, ProjectEvent $activity): bool
    {
        return $this->allowsManage($user);
    }

    public function updateAny(User $user): bool
    {
        return $this->allowsManage($user);
    }

    public function delete(User $user, ProjectEvent $activity): bool
    {
        return $this->allowsManage($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowsManage($user);
    }

    private function allowsManage(User $user): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, ProjectAccess::ABILITY_MANAGE_SETTINGS, $project);
    }

    private function isProjectMember(User $user): bool
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return false;
        }

        if (app(ProjectAccess::class)->can($user, ProjectAccess::ABILITY_MANAGE_SETTINGS, $project)) {
            return true;
        }

        return $user->roleFor($project) !== null;
    }
}

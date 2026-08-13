<?php

declare(strict_types=1);

namespace App\Policies\Project;

use App\Enums\Permissions\ProjectEventPermission;
use App\Models\Project;
use App\Models\Project\ProjectEvent;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ProjectEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectEventPermission::ViewAny->value);
    }

    public function view(User $user, ProjectEvent $activity): bool
    {
        return $this->allows($user, ProjectEventPermission::ViewAny->value);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectEventPermission::Create->value);
    }

    public function update(User $user, ProjectEvent $activity): bool
    {
        return $this->allows($user, ProjectEventPermission::Update->value);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, ProjectEventPermission::UpdateAny->value);
    }

    public function delete(User $user, ProjectEvent $activity): bool
    {
        return $this->allows($user, ProjectEventPermission::Delete->value);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectEventPermission::DeleteAny->value);
    }

    private function allows(User $user, string $permission): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $permission, $project);
    }
}

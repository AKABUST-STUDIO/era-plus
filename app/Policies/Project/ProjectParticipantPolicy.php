<?php

declare(strict_types=1);

namespace App\Policies\Project;

use App\Models\Project;
use App\Models\Project\ProjectParticipant;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ProjectParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function view(User $user, ProjectParticipant $projectParticipant): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function update(User $user, ProjectParticipant $projectParticipant): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function delete(User $user, ProjectParticipant $projectParticipant): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    private function allows(User $user, string $ability): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $ability, $project);
    }
}

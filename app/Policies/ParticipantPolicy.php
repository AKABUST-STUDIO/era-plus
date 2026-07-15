<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Participant;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function view(User $user, Participant $participant): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function update(User $user, Participant $participant): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function delete(User $user, Participant $participant): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function restore(User $user, Participant $participant): bool
    {
        return $this->allows($user, ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public function forceDelete(User $user, Participant $participant): bool
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

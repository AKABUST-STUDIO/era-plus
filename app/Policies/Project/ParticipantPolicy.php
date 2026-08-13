<?php

declare(strict_types=1);

namespace App\Policies\Project;

use App\Enums\Permissions\ParticipantPermission;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\User;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;

class ParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, ParticipantPermission::ViewAny->value);
    }

    public function view(User $user, Participant $participant): bool
    {
        return $this->allows($user, ParticipantPermission::ViewAny->value);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, ParticipantPermission::Create->value);
    }

    public function update(User $user, Participant $participant): bool
    {
        return $this->allows($user, ParticipantPermission::Update->value);
    }

    public function updateAny(User $user): bool
    {
        return $this->allows($user, ParticipantPermission::UpdateAny->value);
    }

    public function delete(User $user, Participant $participant): bool
    {
        return $this->allows($user, ParticipantPermission::Delete->value);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, ParticipantPermission::DeleteAny->value);
    }

    private function allows(User $user, string $permission): bool
    {
        $project = Filament::getTenant();

        return $project instanceof Project
            && app(ProjectAccess::class)->can($user, $permission, $project);
    }
}

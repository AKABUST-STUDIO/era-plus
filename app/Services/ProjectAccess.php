<?php

namespace App\Services;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class ProjectAccess
{
    public const ABILITY_VIEW_FINANCE = 'project.view_finance';

    public const ABILITY_MANAGE_FINANCE = 'project.manage_finance';

    public const ABILITY_MANAGE_MEMBERS = 'project.manage_members';

    public const ABILITY_MANAGE_PARTICIPANTS = 'project.manage_participants';

    public const ABILITY_MANAGE_TASKS = 'project.manage_tasks';

    public const ABILITY_MANAGE_SETTINGS = 'project.manage_settings';

    public function can(User $user, string $ability, Project $project): bool
    {
        if ($user->isOrgAdmin($project->organization)) {
            return true;
        }

        $role = $this->projectRole($user, $project);

        if ($role === null) {
            return false;
        }

        return match ($ability) {
            self::ABILITY_VIEW_FINANCE => in_array($role, [
                ProjectRole::Coordinator, ProjectRole::Leader,
            ], true),
            self::ABILITY_MANAGE_FINANCE,
            self::ABILITY_MANAGE_MEMBERS,
            self::ABILITY_MANAGE_PARTICIPANTS,
            self::ABILITY_MANAGE_SETTINGS => $role === ProjectRole::Coordinator,
            self::ABILITY_MANAGE_TASKS => in_array($role, [
                ProjectRole::Coordinator, ProjectRole::Leader,
            ], true),
            default => false,
        };
    }

    public function projectRole(User $user, Project $project): ?ProjectRole
    {
        $pivot = $user->projects()->whereKey($project->id)->first()?->pivot;

        if ($pivot === null) {
            return null;
        }

        $value = $pivot->role;

        if ($value instanceof ProjectRole) {
            return $value;
        }

        return ProjectRole::tryFrom((string) $value);
    }
}

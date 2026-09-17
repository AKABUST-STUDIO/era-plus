<?php

namespace App\Filament\Project\Settings\Resources\ProjectMembers\Components;

use App\Enums\Project\ProjectRole;
use App\Facades\ProjectService;
use App\Models\Project;
use Filament\Forms\Components\Select;

class RoleSelect
{
    public static function make(string $name = 'role'): Select
    {
        return Select::make($name)
            ->hiddenLabel()
            ->options(fn (): array => ProjectService::current()?->roleOptions() ?? [])
            ->default(fn (): ?int => self::defaultRoleId())
            ->required();
    }

    public static function defaultRoleId(): ?int
    {
        $project = ProjectService::current();

        if (! $project instanceof Project) {
            return null;
        }

        return $project->roles()->where('locked', false)->orderBy('id')->value('id')
            ?? $project->roleFor(ProjectRole::Participant)?->id;
    }
}

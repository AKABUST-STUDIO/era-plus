<?php

namespace App\Filament\Project\Resources\ProjectMembers\Schemas;

use App\Enums\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Arrayable;

class ProjectMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Member')
                    ->options(fn (): Arrayable => self::orgMembersNotYetInProject())
                    ->searchable()
                    ->required()
                    ->disabledOn('edit'),
                Select::make('role')
                    ->options(ProjectRole::class)
                    ->default(ProjectRole::Participant)
                    ->required(),
            ]);
    }

    /**
     * @return Arrayable<int, string>
     */
    private static function orgMembersNotYetInProject(): Arrayable
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return collect();
        }

        /** @var Organization $organization */
        $organization = $project->organization;

        $existingUserIds = $project->users()->pluck('users.id');

        return User::query()
            ->whereHas('organizations', fn ($query) => $query->whereKey($organization->id))
            ->whereNotIn('id', $existingUserIds)
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}

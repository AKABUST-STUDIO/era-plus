<?php

namespace App\Filament\Project\Resources\ProjectMembers;

use App\Filament\Project\Resources\ProjectMembers\Pages\CreateProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\EditProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Resources\ProjectMembers\Schemas\ProjectMemberForm;
use App\Filament\Project\Resources\ProjectMembers\Tables\ProjectMembersTable;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProjectMemberResource extends Resource
{
    protected static ?string $model = ProjectMember::class;

    public static function getNavigationLabel(): string
    {
        return __('navigation.members');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.member');
    }

    protected static ?string $pluralModelLabel = 'members';

    public static function form(Schema $schema): Schema
    {
        return ProjectMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectMembers::route('/'),
            'create' => CreateProjectMember::route('/create'),
            'edit' => EditProjectMember::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public static function canCreate(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public static function canEdit(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    public static function canDelete(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_MEMBERS);
    }

    private static function userCan(string $ability): bool
    {
        $project = Filament::getTenant();
        $user = auth()->user();

        if (! $project instanceof Project || $user === null) {
            return false;
        }

        return app(ProjectAccess::class)->can($user, $ability, $project);
    }
}

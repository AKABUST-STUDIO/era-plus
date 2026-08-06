<?php

namespace App\Filament\Project\Resources\ProjectMembers;

use App\Filament\Project\Resources\ProjectMembers\Pages\CreateProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\EditProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Resources\ProjectMembers\Schemas\ProjectMemberForm;
use App\Filament\Project\Resources\ProjectMembers\Tables\ProjectMembersTable;
use App\Models\ProjectUser;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProjectMemberResource extends Resource
{
    protected static ?string $model = ProjectUser::class;

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
}

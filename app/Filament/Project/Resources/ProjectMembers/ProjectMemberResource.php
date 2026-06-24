<?php

namespace App\Filament\Project\Resources\ProjectMembers;

use App\Filament\Project\Resources\ProjectMembers\Pages\CreateProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\EditProjectMember;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Resources\ProjectMembers\Schemas\ProjectMemberForm;
use App\Filament\Project\Resources\ProjectMembers\Tables\ProjectMembersTable;
use App\Models\ProjectMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectMemberResource extends Resource
{
    protected static ?string $model = ProjectMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'member';

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

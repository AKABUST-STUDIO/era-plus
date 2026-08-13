<?php

namespace App\Filament\Project\Resources\ProjectMembers;

use App\Filament\Contracts\HasProjectPermissions;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Resources\ProjectMembers\Tables\ProjectMembersTable;
use App\Models\ProjectUser;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectMemberResource extends Resource implements HasProjectPermissions
{
    protected static ?string $model = ProjectUser::class;

    protected static ?string $slug = 'users';

    public static function getNavigationLabel(): string
    {
        return __('navigation.users');
    }

    public static function getModelLabel(): string
    {
        return __('member.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('member.title');
    }

    public static function table(Table $table): Table
    {
        return ProjectMembersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'role']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectMembers::route('/'),
        ];
    }
}

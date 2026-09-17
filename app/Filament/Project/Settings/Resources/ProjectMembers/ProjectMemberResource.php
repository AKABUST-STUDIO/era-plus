<?php

namespace App\Filament\Project\Settings\Resources\ProjectMembers;

use App\Filament\Contracts\HasProjectPermissions;
use App\Filament\Project\Settings\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Project\Settings\Resources\ProjectMembers\Tables\ProjectMembersTable;
use App\Models\ProjectUser;
use App\Services\PermissionRegistry;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectMemberResource extends Resource implements HasProjectPermissions
{
    protected static ?string $model = ProjectUser::class;

    protected static ?string $slug = 'users';

    /**
     * @return list<string>|null
     */
    public static function getPermissionActions(string $scope): ?array
    {
        return $scope === PermissionRegistry::SCOPE_PROJECT
            ? ['view_any', 'view', 'create', 'update_any', 'delete_any']
            : null;
    }

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

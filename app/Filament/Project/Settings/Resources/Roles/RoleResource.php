<?php

namespace App\Filament\Project\Settings\Resources\Roles;

use App\Facades\ProjectService;
use App\Filament\Contracts\HasProjectPermissions;
use App\Filament\Project\Settings\Resources\Roles\Pages\EditRole;
use App\Filament\Project\Settings\Resources\Roles\Pages\ListRoles;
use App\Filament\Project\Settings\Resources\Roles\Schemas\RoleForm;
use App\Filament\Project\Settings\Resources\Roles\Tables\RolesTable;
use App\Models\Project;
use App\Models\Role;
use App\Services\PermissionRegistry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource implements HasProjectPermissions
{
    protected static ?string $model = Role::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 15;

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
        return __('settings.roles.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('settings.roles.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('settings.roles.title');
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $project = self::project();

        return parent::getEloquentQuery()
            ->when(
                $project instanceof Project,
                fn (Builder $q): Builder => $q->where('roleable_type', $project->getMorphClass())
                    ->where('roleable_id', $project->id),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function project(): ?Project
    {
        return ProjectService::current();
    }
}

<?php

namespace App\Filament\Organization\Settings\Resources\Roles;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Resources\Roles\Pages\EditRole;
use App\Filament\Organization\Settings\Resources\Roles\Pages\ListRoles;
use App\Filament\Organization\Settings\Resources\Roles\Schemas\RoleForm;
use App\Filament\Organization\Settings\Resources\Roles\Tables\RolesTable;
use App\Models\Organization;
use App\Models\Role;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 15;

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
        $organization = self::organization();

        return parent::getEloquentQuery()
            ->when(
                $organization instanceof Organization,
                fn (Builder $q): Builder => $q->where('roleable_type', $organization->getMorphClass())
                    ->where('roleable_id', $organization->id),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function organization(): ?Organization
    {
        return OrganizationService::current();
    }

    public static function resourceKey(string $resource): string
    {
        return 'permissions_'.$resource;
    }
}

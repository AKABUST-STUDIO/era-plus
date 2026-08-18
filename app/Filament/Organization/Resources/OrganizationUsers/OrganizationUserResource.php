<?php

namespace App\Filament\Organization\Resources\OrganizationUsers;

use App\Facades\OrganizationService;
use App\Filament\Contracts\HasOrganizationPermissions;
use App\Filament\Organization\Resources\OrganizationUsers\Pages\ListOrganizationUsers;
use App\Filament\Organization\Resources\OrganizationUsers\Tables\OrganizationUsersTable;
use App\Models\Organization;
use App\Models\Organization\OrganizationUser;
use App\Services\PermissionRegistry;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationUserResource extends Resource implements HasOrganizationPermissions
{
    protected static ?string $model = OrganizationUser::class;

    protected static ?string $slug = 'users';

    protected static ?int $navigationSort = 10;

    /**
     * @return list<string>|null
     */
    public static function getPermissionActions(string $scope): ?array
    {
        return $scope === PermissionRegistry::SCOPE_ORGANIZATION ? ['view'] : null;
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('settings.users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('settings.users.title');
    }

    public static function table(Table $table): Table
    {
        return OrganizationUsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $organization = self::organization();

        return parent::getEloquentQuery()
            ->with(['user', 'role'])
            ->when(
                $organization instanceof Organization,
                fn (Builder $query): Builder => $query->where('organization_id', $organization->getKey()),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationUsers::route('/'),
        ];
    }

    public static function organization(): ?Organization
    {
        return OrganizationService::current();
    }
}

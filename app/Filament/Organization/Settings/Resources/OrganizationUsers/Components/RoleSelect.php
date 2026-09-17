<?php

namespace App\Filament\Organization\Settings\Resources\OrganizationUsers\Components;

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Resources\Roles\RoleResource;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

class RoleSelect
{
    public static function make(string $name = 'role'): Select
    {
        return Select::make($name)
            ->hiddenLabel()
            ->options(fn (): array => OrganizationService::current()->roleOptions())
            ->default(fn (): ?int => self::defaultRoleId())
            ->required()
            ->suffixActions([
                Action::make('manageRoles')
                    ->icon('lucide-external-link')
                    ->color('gray')
                    ->tooltip(__('settings.users.role_select.manage'))
                    ->url(fn (): string => RoleResource::getUrl(panel: SettingsPanelProvider::PANEL_ID))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function defaultRoleId(): ?int
    {
        $organization = OrganizationService::current();

        return $organization->roles()->where('locked', false)->orderBy('id')->value('id')
            ?? $organization->roleFor(OrganizationRole::Admin)?->id;
    }
}

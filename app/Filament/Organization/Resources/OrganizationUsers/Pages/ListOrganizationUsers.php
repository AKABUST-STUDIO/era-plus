<?php

namespace App\Filament\Organization\Resources\OrganizationUsers\Pages;

use App\Filament\Organization\Resources\OrganizationUsers\Actions\InviteUserAction;
use App\Filament\Organization\Resources\OrganizationUsers\OrganizationUserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrganizationUsers extends ListRecords
{
    public const TAB_USERS = 'users';

    public const TAB_INVITATIONS = 'invitations';

    protected static string $resource = OrganizationUserResource::class;

    public function getTitle(): string
    {
        return __('settings.users.title');
    }

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            InviteUserAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            self::TAB_USERS => Tab::make(__('settings.users.tabs.members'))
                ->badge(fn (): int => OrganizationUserResource::getEloquentQuery()
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))),
            self::TAB_INVITATIONS => Tab::make(__('settings.users.tabs.invitations'))
                ->badge(fn (): int => OrganizationUserResource::getEloquentQuery()
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNull('email_verified_at'))),
        ];
    }
}

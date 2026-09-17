<?php

namespace App\Filament\Project\Settings\Resources\ProjectMembers\Pages;

use App\Filament\Project\Settings\Resources\ProjectMembers\Actions\InviteMemberAction;
use App\Filament\Project\Settings\Resources\ProjectMembers\ProjectMemberResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProjectMembers extends ListRecords
{
    public const TAB_MEMBERS = 'members';

    public const TAB_INVITATIONS = 'invitations';

    protected static string $resource = ProjectMemberResource::class;

    public function getTitle(): string
    {
        return __('member.title');
    }

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            InviteMemberAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            self::TAB_MEMBERS => Tab::make(__('member.tabs.members'))
                ->badge(fn (): int => ProjectMemberResource::getEloquentQuery()
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))),
            self::TAB_INVITATIONS => Tab::make(__('member.tabs.invitations'))
                ->badge(fn (): int => ProjectMemberResource::getEloquentQuery()
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereHas('user', fn (Builder $query): Builder => $query->whereNull('email_verified_at'))),
        ];
    }
}

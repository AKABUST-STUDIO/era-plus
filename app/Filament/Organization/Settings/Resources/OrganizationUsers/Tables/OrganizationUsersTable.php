<?php

namespace App\Filament\Organization\Settings\Resources\OrganizationUsers\Tables;

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Resources\OrganizationUsers\Components\RoleSelect;
use App\Filament\Organization\Settings\Resources\OrganizationUsers\Pages\ListOrganizationUsers;
use App\Filament\Tables\Filters\FilterGroup;
use App\Filament\Tables\Filters\SearchFilter;
use App\Models\ActivityLog;
use App\Models\Organization\OrganizationUser;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationUsersTable
{
    public static function configure(Table $table): Table
    {
        $organization = OrganizationService::current();

        return $table
            ->paginated(fn (HasTable $livewire): bool => ($livewire->getFilteredTableQuery()?->count() ?? 0) > 10)
            ->defaultSort('created_at', 'desc')
            ->defaultSortOptionLabel(__('settings.users.sort.date'))
            ->emptyStateIcon(fn (HasTable $livewire): string => self::isInvitationsTab($livewire)
                ? 'lucide-mail'
                : 'lucide-users-round')
            ->emptyStateHeading(fn (HasTable $livewire): string => self::isInvitationsTab($livewire)
                ? __('settings.users.empty.invitations_heading')
                : __('settings.users.empty.members_heading'))
            ->emptyStateDescription(fn (HasTable $livewire): string => self::isInvitationsTab($livewire)
                ? __('settings.users.empty.invitations_description')
                : __('settings.users.empty.members_description'))
            ->checkIfRecordIsSelectableUsing(fn (OrganizationUser $record): bool => auth()->user()->can('removeMember', [$organization, $record->user]))
            ->columns([
                Split::make([
                    ImageColumn::make('user.avatar')
                        ->getStateUsing(fn (OrganizationUser $record): string => $record->user->avatarUrl())
                        ->circular()
                        ->grow(false),
                    Stack::make([
                        Split::make([])
                            ->grow(false)
                            ->schema([
                                TextColumn::make('user.name')
                                    ->weight('bold')
                                    ->searchable()
                                    ->sortable(),
                                TextColumn::make('status_badge')
                                    ->getStateUsing(fn (OrganizationUser $record): ?string => match (true) {
                                        $record->user->email_verified_at === null => 'pending',
                                        $record->user->is(auth()->user()) => 'you',
                                        default => null,
                                    })
                                    ->formatStateUsing(fn (string $state): string => __("settings.users.table.{$state}"))
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'pending' ? 'warning' : 'gray')
                                    ->icon(fn (string $state): ?string => $state === 'pending' ? 'lucide-clock' : null)
                                    ->grow(false),
                            ]),
                        TextColumn::make('user.email')
                            ->color('gray')
                            ->searchable(),
                    ]),
                    TextColumn::make('role_label')
                        ->getStateUsing(fn (OrganizationUser $record): ?string => $record->role?->displayLabel())
                        ->badge()
                        ->color('gray')
                        ->grow(false),
                ])
                    ->extraAttributes(fn (OrganizationUser $record): array => $record->user->email_verified_at === null
                        ? ['class' => 'opacity-60']
                        : []),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->searchable(false)
            ->hiddenFilterIndicators(true)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->columnManager(false)
            ->groupingSettingsHidden()
            ->filtersFormColumns(4)
            ->filters([
                SearchFilter::make()
                    ->columnSpan(2)
                    ->columnStart(2),
                FilterGroup::make(
                    schema: [
                        Select::make('role')
                            ->hiddenLabel()
                            ->prefix(__('forms.common.role'))
                            ->placeholder(__('settings.users.filters.role_any'))
                            ->options($organization->roleOptions()),
                    ],
                    query: fn (Builder $query, array $data): Builder => $query
                        ->when($data['role'] ?? null, fn (Builder $query, string $roleId): Builder => $query->where('role_id', $roleId))
                        ->when(filled($data['two_factor'] ?? null), fn (Builder $query): Builder => $query->whereHas(
                            'user',
                            fn (Builder $query): Builder => $data['two_factor'] === '1'
                                ? $query->whereNotNull('two_factor_confirmed_at')
                                : $query->whereNull('two_factor_confirmed_at'),
                        ))
                )
                    ->columnStart(4),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('changeRole')
                        ->label(__('settings.users.actions.change_role'))
                        ->icon('lucide-refresh-cw')
                        ->authorize('update')
                        ->authorizationMessage(__('settings.users.actions.sole_admin_locked'))
                        ->authorizationTooltip()
                        ->modalWidth(Width::ExtraSmall)
                        ->modalCloseButton(false)
                        ->modalCancelAction(false)
                        ->modalFooterActionsAlignment(Alignment::End)
                        ->schema([
                            RoleSelect::make()
                                ->default(fn (OrganizationUser $record): ?int => $record->role_id),
                        ])
                        ->action(fn (OrganizationUser $record, array $data) => self::changeRole($record, (int) $data['role'])),
                    DeleteAction::make()
                        ->label(__('settings.users.actions.remove'))
                        ->icon('lucide-trash-2')
                        ->modalWidth(Width::ExtraSmall)
                        ->modalCloseButton(false)
                        ->modalCancelAction(false)
                        ->authorizationMessage(__('settings.users.actions.sole_admin_locked'))
                        ->authorizationTooltip()
                        ->after(fn (OrganizationUser $record) => ActivityLog::record(
                            $record->organization,
                            "Removed {$record->user->email}",
                            eventType: 'organization.member.removed',
                            target: $record->user,
                        )),
                ]),
            ]);
    }

    private static function isInvitationsTab(HasTable $livewire): bool
    {
        return ($livewire->activeTab ?? null) === ListOrganizationUsers::TAB_INVITATIONS;
    }

    public static function changeRole(OrganizationUser $record, int $roleId): void
    {
        $organization = $record->organization;

        $role = $organization->roles()->find($roleId);

        if ($role === null) {
            Notification::make()->title(__('notifications.invalid_role'))->danger()->send();

            return;
        }

        $record->update(['role_id' => $role->id]);

        $roleLabel = OrganizationRole::tryFrom($role->name)?->getLabel() ?? $role->name;

        ActivityLog::record(
            $organization,
            "Set {$record->user->email} role to {$roleLabel}",
            eventType: 'organization.member.role_changed',
            target: $record->user,
            data: ['role' => $role->name],
        );

        Notification::make()->title(__('notifications.role_updated'))->success()->send();
    }

    public static function remove(OrganizationUser $record): void
    {
        $organization = $record->organization;
        $user = $record->user;

        $record->delete();

        ActivityLog::record(
            $organization,
            "Removed {$user->email}",
            eventType: 'organization.member.removed',
            target: $user,
        );
    }
}

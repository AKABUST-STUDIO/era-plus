<?php

namespace App\Filament\Organization\Pages;

use App\Enums\Organization\OrganizationRole;
use App\Facades\OrganizationService;
use App\Filament\Concerns\GatedByOrganizationPermission;
use App\Filament\Organization\Settings\Resources\Roles\RoleResource;
use App\Mail\OrganizationInvitation;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use App\Providers\Filament\Organization\SettingsPanelProvider;
use App\Support\EmailUsername;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrganizationUsers extends Page implements HasTable
{
    use GatedByOrganizationPermission;
    use InteractsWithTable;

    public const TAB_USERS = 'users';

    public const TAB_INVITATIONS = 'invitations';

    protected static ?string $slug = 'users';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.organization.pages.users';

    public ?Organization $organization = null;

    public string $activeTab = self::TAB_USERS;

    /**
     * @var array<string, mixed>
     */
    public ?array $inviteData = [];

    protected static function organizationPermission(): string
    {
        return 'view_any_member';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.users.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.users.title');
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;

        $this->inviteForm->fill(['role' => $this->defaultRoleId()]);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, [self::TAB_USERS, self::TAB_INVITATIONS], true)
            ? $tab
            : self::TAB_USERS;

        $this->resetTable();
    }

    /**
     * @return array<string, array{label: string, count: int}>
     */
    public function getTabs(): array
    {
        return [
            self::TAB_USERS => [
                'label' => __('settings.users.tabs.members'),
                'count' => $this->tabQuery(self::TAB_USERS)->count(),
            ],
            self::TAB_INVITATIONS => [
                'label' => __('settings.users.tabs.invitations'),
                'count' => $this->tabQuery(self::TAB_INVITATIONS)->count(),
            ],
        ];
    }

    public function inviteForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('inviteData')
            ->components([
                Section::make(__('settings.users.invite.heading'))
                    ->description(__('settings.users.invite.description'))
                    ->visible(fn (): bool => auth()->user()->can('update', $this->organization))
                    ->columns(4)
                    ->schema([
                        TextInput::make('email')
                            ->hiddenLabel()
                            ->placeholder(__('settings.users.invite.email'))
                            ->prefixIcon('lucide-mail')
                            ->columnSpan(2)
                            ->email()
                            ->required(),
                        Select::make('role')
                            ->hiddenLabel()
                            ->prefix('Role')
                            ->options(fn (): array => $this->organization->roleOptions())
                            ->default(fn (): ?int => $this->defaultRoleId())
                            ->required()
                            ->suffixActions($this->roleSelectSuffixActions('inviteData.role')),
                    ])
                    ->footerActions([
                        Action::make('invite')
                            ->label(__('settings.users.invite.action'))
                            ->action(fn () => $this->invite()),
                    ]),
            ]);
    }

    public function invite(): void
    {
        if (auth()->user()->cannot('update', $this->organization)) {
            Notification::make()
                ->title(__('notifications.cannot_invite'))
                ->danger()
                ->send();

            return;
        }

        $data = $this->inviteForm->getState();

        $user = User::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => EmailUsername::toDisplayName($data['email']),
                'password' => Str::random(64),
            ],
        );

        if ($this->organization->users()->whereKey($user->id)->exists()) {
            Notification::make()->title(__('notifications.already_member'))->warning()->send();

            return;
        }

        $roleId = (int) ($data['role'] ?? $this->defaultRoleId());
        $role = $this->organization->roles()->find($roleId);

        if ($role === null) {
            Notification::make()->title(__('notifications.invalid_role'))->danger()->send();

            return;
        }

        $this->organization->users()->attach($user, ['role_id' => $role->id]);

        Mail::to($user->email)->queue(new OrganizationInvitation(
            $this->organization,
            $user,
            $role,
            auth()->user(),
        ));

        $roleLabel = OrganizationRole::tryFrom($role->name)?->getLabel() ?? $role->name;

        ActivityLog::record(
            $this->organization,
            "Invited {$user->email} as {$roleLabel}",
            eventType: 'organization.member.invited',
            target: $user,
            data: ['email' => $user->email, 'role' => $role->name],
        );

        Notification::make()->title(__('notifications.invitation_sent'))->success()->send();

        $this->inviteForm->fill(['role' => $this->defaultRoleId()]);
    }

    private function canChangeRole(User $target): bool
    {
        return auth()->user()->can('update', $this->organization)
            && ! OrganizationService::isSoleAdmin($this->organization, $target);
    }

    private function canRemove(User $target): bool
    {
        if (! auth()->user()->can('update', $this->organization) && ! $target->is(auth()->user())) {
            return false;
        }

        return ! OrganizationService::isSoleAdmin($this->organization, $target);
    }

    private function defaultRoleId(): ?int
    {
        $firstCustom = $this->organization->roles()->where('locked', false)->orderBy('id')->value('id');

        return $firstCustom
            ?? $this->organization->roleFor(OrganizationRole::Admin)?->id;
    }

    /**
     * @return array<int, Action>
     */
    private function roleSelectSuffixActions(string $statePath): array
    {
        return [
            Action::make('manageRoles')
                ->icon('lucide-external-link')
                ->color('gray')
                ->tooltip(__('settings.users.role_select.manage'))
                ->url(fn (): string => RoleResource::getUrl(panel: SettingsPanelProvider::PANEL_ID))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): BelongsToMany => $this->tabQuery($this->activeTab))
            ->defaultSort('organization_user.created_at', 'desc')
            ->defaultSortOptionLabel(__('settings.users.sort.date'))
            ->emptyStateIcon($this->activeTab === self::TAB_INVITATIONS ? 'lucide-mail' : 'lucide-users-round')
            ->emptyStateHeading($this->activeTab === self::TAB_INVITATIONS
                ? __('settings.users.empty.invitations_heading')
                : __('settings.users.empty.members_heading'))
            ->emptyStateDescription($this->activeTab === self::TAB_INVITATIONS
                ? __('settings.users.empty.invitations_description')
                : __('settings.users.empty.members_description'))
            ->checkIfRecordIsSelectableUsing(fn (User $record): bool => $this->canRemove($record))
            ->columns([
                Split::make([
                    ImageColumn::make('avatar')
                        ->getStateUsing(fn (User $user): string => $user->avatarUrl())
                        ->circular()
                        ->grow(false),
                    Stack::make([
                        Split::make([])
                            ->grow(false)
                            ->schema([
                                TextColumn::make('name')
                                    ->weight('bold')
                                    ->searchable(['name', 'email'])
                                    ->sortable(),
                                TextColumn::make('you_badge')
                                    ->getStateUsing(fn (User $record): ?string => $record->is(auth()->user())
                                        ? __('settings.users.table.you')
                                        : null)
                                    ->badge()
                                    ->color('gray')
                                    ->grow(false),
                            ]),
                        TextColumn::make('email')
                            ->color('gray')
                            ->searchable(),
                    ]),
                    TextColumn::make('role_label')
                        ->getStateUsing(fn (User $record): ?string => $record->member?->role?->displayLabel())
                        ->badge()
                        ->color('gray')
                        ->grow(false),
                    TextColumn::make('two_factor_label')
                        ->getStateUsing(fn (): string => __('settings.users.table.two_factor'))
                        ->badge()
                        ->color('gray')
                        ->size(TextSize::Small)
                        ->icon(fn (User $record): string => $record->two_factor_confirmed_at !== null
                            ? 'lucide-check-circle'
                            : 'lucide-x-circle')
                        ->tooltip(fn (User $record): string => $record->two_factor_confirmed_at !== null
                            ? __('settings.users.table.two_factor_on')
                            : __('settings.users.table.two_factor_off'))
                        ->grow(false),
                ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('role')
                    ->label(__('forms.common.role'))
                    ->options($this->organization->roleOptions())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->where('organization_user.role_id', $data['value'])
                        : $query),
                TernaryFilter::make('two_factor_confirmed_at')
                    ->label(__('settings.users.filters.two_factor'))
                    ->placeholder(__('settings.users.filters.two_factor_any'))
                    ->trueLabel(__('settings.users.filters.two_factor_on'))
                    ->falseLabel(__('settings.users.filters.two_factor_off'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('two_factor_confirmed_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('two_factor_confirmed_at'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('changeRole')
                        ->label(__('settings.users.actions.change_role'))
                        ->icon('lucide-refresh-cw')
                        ->disabled(fn (User $user): bool => ! $this->canChangeRole($user))
                        ->tooltip(fn (User $user): ?string => $this->canChangeRole($user)
                            ? null
                            : __('settings.users.actions.sole_admin_locked'))
                        ->form([
                            Select::make('role')
                                ->options(fn (): array => $this->organization->roleOptions())
                                ->default(fn (User $user): ?int => $user->member?->role_id)
                                ->required()
                                ->suffixActions($this->roleSelectSuffixActions('mountedActions.0.data.role')),
                        ])
                        ->action(fn (User $user, array $data) => $this->changeRole($user, (int) $data['role'])),
                    Action::make('remove')
                        ->label(__('settings.users.actions.remove'))
                        ->icon('lucide-trash-2')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->disabled(fn (User $user): bool => ! $this->canRemove($user))
                        ->tooltip(fn (User $user): ?string => $this->canRemove($user)
                            ? null
                            : __('settings.users.actions.sole_admin_locked'))
                        ->action(fn (User $user) => $this->removeUser($user)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('removeBulk')
                        ->label(__('settings.users.actions.remove'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(
                            fn (User $user) => $this->canRemove($user)
                                ? $this->removeUser($user)
                                : null
                        )),
                ]),
            ]);
    }

    public function changeRole(User $user, int $roleId): void
    {
        if (! $this->canChangeRole($user)) {
            Notification::make()
                ->title(__('notifications.cannot_remove_last_admin'))
                ->danger()
                ->send();

            return;
        }

        $role = $this->organization->roles()->find($roleId);

        if ($role === null) {
            Notification::make()->title(__('notifications.invalid_role'))->danger()->send();

            return;
        }

        $this->organization->users()->updateExistingPivot($user->id, ['role_id' => $role->id]);

        $roleLabel = OrganizationRole::tryFrom($role->name)?->getLabel() ?? $role->name;

        ActivityLog::record(
            $this->organization,
            "Set {$user->email} role to {$roleLabel}",
            eventType: 'organization.member.role_changed',
            target: $user,
            data: ['role' => $role->name],
        );

        Notification::make()->title(__('notifications.role_updated'))->success()->send();
    }

    public function removeUser(User $user): void
    {
        if (! $this->canRemove($user)) {
            Notification::make()
                ->title(__('notifications.cannot_remove_last_admin'))
                ->danger()
                ->send();

            return;
        }

        $this->organization->users()->detach($user->id);

        ActivityLog::record(
            $this->organization,
            "Removed {$user->email}",
            eventType: 'organization.member.removed',
            target: $user,
        );

    }

    private function tabQuery(string $tab): BelongsToMany
    {
        /**
         * The pivot carries its own `id`, which overwrites `users.id` on the record
         * unless the user columns are selected explicitly.
         */
        $query = $this->organization->users()->select('users.*');

        return match ($tab) {
            self::TAB_INVITATIONS => $query->whereNull('users.email_verified_at'),
            default => $query->whereNotNull('users.email_verified_at'),
        };
    }
}

<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Enums\OrganizationRole;
use App\Facades\OrganizationService;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Users extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'users';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.organization.settings.pages.users';

    public ?Organization $organization = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $inviteData = [];

    public static function getNavigationLabel(): string
    {
        return __('settings.users.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.users.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('settings.breadcrumb'),
            __('settings.users.navigation_label'),
        ];
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;

        $this->inviteForm->fill(['role' => OrganizationRole::Member->value]);
    }

    public function inviteForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('inviteData')
            ->components([
                Section::make(__('settings.users.invite.heading'))
                    ->description(__('settings.users.invite.description'))
                    ->schema([
                        TextInput::make('email')
                            ->label(__('settings.users.invite.email'))
                            ->email()
                            ->required(),
                        Select::make('role')
                            ->options(OrganizationRole::class)
                            ->default(OrganizationRole::Member)
                            ->required(),
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
        $data = $this->inviteForm->getState();

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user) {
            $user = User::create([
                'name' => Str::before($data['email'], '@'),
                'email' => $data['email'],
                'password' => bcrypt(Str::random(40)),
            ]);
        }

        if ($this->organization->users()->whereKey($user->id)->exists()) {
            Notification::make()->title('User is already a member')->warning()->send();

            return;
        }

        $role = $data['role'] ?? null;
        $role = $role instanceof OrganizationRole
            ? $role
            : (OrganizationRole::tryFrom((string) $role) ?? OrganizationRole::Member);

        $this->organization->users()->attach($user, [
            'role' => $role->value,
            'is_admin' => $role === OrganizationRole::Admin,
        ]);

        ActivityLog::record(
            $this->organization,
            "Invited {$user->email} as {$role->getLabel()}",
            eventType: 'organization.member.invited',
            target: $user,
            data: ['email' => $user->email, 'role' => $role->value],
        );

        Notification::make()->title('Member added')->success()->send();

        $this->inviteForm->fill(['role' => OrganizationRole::Member->value]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): BelongsToMany => $this->organization->users())
            ->columns([
                TextColumn::make('name')
                    ->label(__('settings.users.table.name'))
                    ->description(fn (User $r): ?string => $r->email)
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (User $r, string $state): string => $r->is($this->authUser())
                        ? $state.' (YOU)'
                        : $state)
                    ->color(fn (User $r): string => $r->is($this->authUser()) ? 'primary' : 'gray'),
                TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => OrganizationRole::tryFrom((string) $state)?->getLabel() ?? '—')
                    ->color(fn (?string $state): string => OrganizationRole::tryFrom((string) $state)?->getColor() ?? 'gray'),
                TextColumn::make('pivot.created_at')
                    ->label(__('settings.users.table.joined'))
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(OrganizationRole::class)
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->wherePivot('role', $data['value'])
                        : $query),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->label('Change role')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (User $r): bool => ! $r->is($this->authUser()))
                    ->form([
                        Select::make('role')
                            ->options(OrganizationRole::class)
                            ->default(fn (User $r): string => (string) $r->pivot->role)
                            ->required(),
                    ])
                    ->action(fn (User $r, array $data) => $this->changeRole($r, OrganizationRole::from($data['role']))),
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $r): bool => ! $r->is($this->authUser()))
                    ->action(fn (User $r) => $this->removeMember($r)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('removeBulk')
                        ->label('Remove')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(
                            fn (User $r) => $r->is($this->authUser()) ? null : $this->removeMember($r)
                        )),
                ]),
            ]);
    }

    public function changeRole(User $user, OrganizationRole $role): void
    {
        $this->organization->users()->updateExistingPivot($user->id, [
            'role' => $role->value,
            'is_admin' => $role === OrganizationRole::Admin,
        ]);

        ActivityLog::record(
            $this->organization,
            "Set {$user->email} role to {$role->getLabel()}",
            eventType: 'organization.member.role_changed',
            target: $user,
            data: ['role' => $role->value],
        );

        Notification::make()->title('Role updated')->success()->send();
    }

    public function removeMember(User $user): void
    {
        if ($user->is($this->authUser())) {
            return;
        }

        if ($user->isOrgAdmin($this->organization) && $this->organization->admins()->count() <= 1) {
            Notification::make()
                ->title('Cannot remove the last Organization Admin')
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

    private function authUser(): User
    {
        return auth()->user();
    }
}

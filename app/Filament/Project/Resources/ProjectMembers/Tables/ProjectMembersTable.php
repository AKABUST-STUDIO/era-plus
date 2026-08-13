<?php

namespace App\Filament\Project\Resources\ProjectMembers\Tables;

use App\Enums\Project\ProjectRole;
use App\Facades\ProjectService;
use App\Filament\Project\Resources\ProjectMembers\Components\RoleSelect;
use App\Filament\Project\Resources\ProjectMembers\Pages\ListProjectMembers;
use App\Filament\Tables\Filters\FilterGroup;
use App\Filament\Tables\Filters\SearchFilter;
use App\Models\ActivityLog;
use App\Models\ProjectUser;
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

class ProjectMembersTable
{
    public static function configure(Table $table): Table
    {
        $project = ProjectService::current();

        return $table
            ->paginated(fn (HasTable $livewire): bool => ($livewire->getFilteredTableQuery()?->count() ?? 0) > 10)
            ->defaultSort('created_at', 'desc')
            ->defaultSortOptionLabel(__('member.sort.date'))
            ->emptyStateIcon(fn (HasTable $livewire): string => self::isInvitationsTab($livewire)
                ? 'lucide-mail'
                : 'lucide-users-round')
            ->emptyStateHeading(fn (HasTable $livewire): string => self::isInvitationsTab($livewire)
                ? __('member.empty.invitations_heading')
                : __('member.empty.members_heading'))
            ->emptyStateDescription(fn (HasTable $livewire): string => self::isInvitationsTab($livewire)
                ? __('member.empty.invitations_description')
                : __('member.empty.members_description'))
            ->columns([
                Split::make([
                    ImageColumn::make('user.avatar')
                        ->getStateUsing(fn (ProjectUser $record): string => $record->user->avatarUrl())
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
                                    ->getStateUsing(fn (ProjectUser $record): ?string => match (true) {
                                        $record->user->email_verified_at === null => 'pending',
                                        $record->user->is(auth()->user()) => 'you',
                                        default => null,
                                    })
                                    ->formatStateUsing(fn (string $state): string => __("member.table.{$state}"))
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
                        ->getStateUsing(fn (ProjectUser $record): ?string => $record->role?->displayLabel())
                        ->badge()
                        ->color('gray')
                        ->grow(false),
                ])
                    ->extraAttributes(fn (ProjectUser $record): array => $record->user->email_verified_at === null
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
                            ->placeholder(__('member.filters.role_any'))
                            ->options($project?->roleOptions() ?? []),
                    ],
                    query: fn (Builder $query, array $data): Builder => $query
                        ->when($data['role'] ?? null, fn (Builder $query, string $roleId): Builder => $query->where('role_id', $roleId)),
                )
                    ->columnStart(4),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('changeRole')
                        ->label(__('member.actions.change_role'))
                        ->icon('lucide-refresh-cw')
                        ->authorize('update')
                        ->modalWidth(Width::ExtraSmall)
                        ->modalCloseButton(false)
                        ->modalCancelAction(false)
                        ->modalFooterActionsAlignment(Alignment::End)
                        ->schema([
                            RoleSelect::make()
                                ->default(fn (ProjectUser $record): ?int => $record->role_id),
                        ])
                        ->action(fn (ProjectUser $record, array $data) => self::changeRole($record, (int) $data['role'])),
                    DeleteAction::make()
                        ->label(__('member.actions.remove'))
                        ->icon('lucide-trash-2')
                        ->modalWidth(Width::ExtraSmall)
                        ->modalCloseButton(false)
                        ->after(fn (ProjectUser $record) => ActivityLog::record(
                            $record->project->organization,
                            "Removed {$record->user->email}",
                            project: $record->project,
                            eventType: 'project.member.removed',
                            target: $record->user,
                        )),
                ]),
            ]);
    }

    private static function isInvitationsTab(HasTable $livewire): bool
    {
        return ($livewire->activeTab ?? null) === ListProjectMembers::TAB_INVITATIONS;
    }

    public static function changeRole(ProjectUser $record, int $roleId): void
    {
        $project = $record->project;

        $role = $project->roles()->find($roleId);

        if ($role === null) {
            Notification::make()->title(__('notifications.invalid_role'))->danger()->send();

            return;
        }

        $record->update(['role_id' => $role->id]);

        $roleLabel = ProjectRole::tryFrom($role->name)?->getLabel() ?? $role->name;

        ActivityLog::record(
            $project->organization,
            "Set {$record->user->email} role to {$roleLabel}",
            project: $project,
            eventType: 'project.member.role_changed',
            target: $record->user,
            data: ['role' => $role->name],
        );

        Notification::make()->title(__('notifications.role_updated'))->success()->send();
    }
}

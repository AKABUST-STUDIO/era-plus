<?php

namespace App\Filament\Project\Settings\Resources\Roles\Tables;

use App\Filament\Project\Settings\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\PermissionRegistrar;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Role $record): string => RoleResource::getUrl('edit', ['record' => $record->id]))
            ->paginated(false)
            ->defaultSort('locked', 'desc')
            ->emptyStateIcon('lucide-shield-check')
            ->emptyStateHeading(__('settings.roles.empty.heading'))
            ->emptyStateDescription(__('settings.roles.empty.description'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('settings.roles.table.label'))
                    ->getStateUsing(fn (Role $record): string => $record->displayLabel())
                    ->icon(fn (Role $record): ?string => $record->locked ? 'lucide-lock' : null)
                    ->iconPosition(IconPosition::After)
                    ->iconColor('warning')
                    ->tooltip(fn (Role $record): ?string => $record->locked
                        ? __('settings.roles.table.locked_tooltip')
                        : null)
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label(__('settings.roles.table.permissions'))
                    ->counts('permissions')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('members_count')
                    ->label(__('settings.roles.table.members'))
                    ->getStateUsing(fn (Role $record): int => self::memberCount($record))
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('lucide-trash-2')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->before(function (DeleteAction $action, Role $record): void {
                        if (self::memberCount($record) > 0) {
                            Notification::make()
                                ->title(__('settings.roles.notifications.delete_has_members'))
                                ->body(__('settings.roles.actions.has_members_tooltip'))
                                ->danger()
                                ->send();

                            $action->cancel();
                        }

                        $record->syncPermissions([]);
                    })
                    ->after(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions())
                    ->successNotificationTitle(__('settings.roles.notifications.deleted')),
            ]);
    }

    private static function memberCount(Role $role): int
    {
        $project = RoleResource::project();

        return $project === null
            ? 0
            : $project->users()->wherePivot('role_id', $role->id)->count();
    }
}

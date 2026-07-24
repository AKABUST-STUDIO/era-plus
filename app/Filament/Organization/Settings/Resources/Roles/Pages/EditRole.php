<?php

namespace App\Filament\Organization\Settings\Resources\Roles\Pages;

use App\Filament\Organization\Settings\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Services\PermissionRegistry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (): void {
                    /** @var Role $role */
                    $role = $this->record;

                    if ($this->memberCount($role) > 0) {
                        Notification::make()
                            ->title(__('settings.roles.notifications.delete_has_members'))
                            ->body(__('settings.roles.actions.has_members_tooltip'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $role->syncPermissions([]);
                    $role->delete();
                    app(PermissionRegistrar::class)->forgetCachedPermissions();

                    Notification::make()->title(__('settings.roles.notifications.deleted'))->success()->send();

                    $this->redirect(RoleResource::getUrl());
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->record;

        $data['label'] = $role->displayLabel();

        $granted = $role->permissions->pluck('name')->all();

        foreach (PermissionRegistry::grouped() as $resource => $permissions) {
            $data[RoleResource::resourceKey($resource)] = array_values(array_intersect($granted, $permissions));
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        $permissions = [];

        foreach (array_keys(PermissionRegistry::grouped()) as $resource) {
            $key = RoleResource::resourceKey($resource);
            $permissions = [...$permissions, ...($data[$key] ?? [])];
        }

        $record->update(['label' => $data['label']]);
        $record->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return RoleResource::getUrl();
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->hidden(fn (): bool => (bool) $this->record?->locked);
    }
}

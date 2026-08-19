<?php

namespace App\Filament\Project\Settings\Resources\Roles\Pages;

use App\Filament\Project\Settings\Pages\Concerns\HasProjectSettingsBreadcrumbs;
use App\Filament\Project\Settings\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Services\PermissionRegistry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    use HasProjectSettingsBreadcrumbs;

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
        $data['permissions'] = array_values(array_intersect(
            $role->permissions->pluck('name')->all(),
            PermissionRegistry::granular(PermissionRegistry::SCOPE_PROJECT),
        ));

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        $record->update(['label' => $data['label']]);
        $record->syncPermissions($data['permissions'] ?? []);
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

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    private function memberCount(Role $role): int
    {
        $project = RoleResource::project();

        return $project === null
            ? 0
            : $project->users()->wherePivot('role_id', $role->id)->count();
    }
}

<?php

namespace App\Filament\Organization\Settings\Resources\Roles\Pages;

use App\Filament\Organization\Settings\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Services\TenantRoleProvisioner;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('settings.roles.actions.create'))
                ->icon('lucide-plus')
                ->modalHeading(__('settings.roles.actions.create_heading'))
                ->modalDescription(__('settings.roles.form.name_helper'))
                ->modalWidth(Width::ExtraSmall)
                ->modalSubmitActionLabel(__('settings.roles.actions.create_submit'))
                ->visible(fn (): bool => RoleResource::canCreate())
                ->modalCancelAction(false)
                ->modalFooterActionsAlignment(Alignment::End)
                ->modalCloseButton(false)
                ->modalIcon('lucide-shield-user')
                ->schema([
                    TextInput::make('name')
                        ->hiddenLabel()
                        ->placeholder(__('settings.roles.form.name'))
                        ->required()
                        ->maxLength(64)
                        ->regex('/^[a-z0-9_.-]+$/i')
                        ->rule(fn () => $this->uniqueRoleNameRule())
                        ->validationMessages([
                            'unique' => __('settings.roles.form.name_unique'),
                            'regex' => __('settings.roles.form.name_regex'),
                        ]),
                ])
                ->action(function (array $data): void {
                    app(TenantRoleProvisioner::class)->ensurePermissions();

                    $organization = RoleResource::organization();
                    $name = Str::lower($data['name']);

                    /** @var Role $role */
                    $role = $organization->roles()->create([
                        'name' => $name,
                        'label' => Str::of($name)->replace(['_', '-'], ' ')->title()->value(),
                        'guard_name' => 'web',
                        'locked' => false,
                    ]);

                    app(PermissionRegistrar::class)->forgetCachedPermissions();

                    Notification::make()->title(__('settings.roles.notifications.created'))->success()->send();

                    $this->redirect(RoleResource::getUrl('edit', ['record' => $role->id]));
                }),
        ];
    }

    private function uniqueRoleNameRule(): ?object
    {
        $organization = RoleResource::organization();

        if ($organization === null) {
            return null;
        }

        return Rule::unique('roles', 'name')
            ->where('roleable_type', $organization->getMorphClass())
            ->where('roleable_id', $organization->id);
    }
}

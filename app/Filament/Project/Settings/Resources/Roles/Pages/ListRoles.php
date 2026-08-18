<?php

namespace App\Filament\Project\Settings\Resources\Roles\Pages;

use App\Filament\Project\Settings\Pages\Concerns\HasProjectSettingsBreadcrumbs;
use App\Filament\Project\Settings\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Services\TenantRoleProvisioner;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class ListRoles extends ListRecords
{
    use HasProjectSettingsBreadcrumbs;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('settings.roles.actions.create'))
                ->icon('lucide-plus')
                ->modalHeading(__('settings.roles.actions.create_heading'))
                ->modalDescription(__('settings.roles.form.name_helper'))
                ->modalWidth(Width::ExtraSmall)
                ->modalSubmitActionLabel(__('settings.roles.actions.create_submit'))
                ->modalCancelAction(false)
                ->modalFooterActionsAlignment(Alignment::End)
                ->modalCloseButton(false)
                ->modalIcon('lucide-shield-user')
                ->createAnother(false)
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
                ->mutateDataUsing(function (array $data): array {
                    $name = Str::lower($data['name']);

                    return [
                        'name' => $name,
                        'label' => Str::of($name)->replace(['_', '-'], ' ')->title()->value(),
                        'guard_name' => 'web',
                        'locked' => false,
                    ];
                })
                ->relationship(fn (): MorphMany => RoleResource::project()->roles())
                ->before(fn () => app(TenantRoleProvisioner::class)->ensurePermissions())
                ->after(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions())
                ->successNotificationTitle(__('settings.roles.notifications.created'))
                ->successRedirectUrl(fn (Role $record): string => RoleResource::getUrl('edit', [
                    'record' => $record->id,
                ])),
        ];
    }

    private function uniqueRoleNameRule(): ?object
    {
        $project = RoleResource::project();

        if ($project === null) {
            return null;
        }

        return Rule::unique('roles', 'name')
            ->where('roleable_type', $project->getMorphClass())
            ->where('roleable_id', $project->id);
    }
}

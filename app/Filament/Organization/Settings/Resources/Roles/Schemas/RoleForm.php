<?php

namespace App\Filament\Organization\Settings\Resources\Roles\Schemas;

use App\Filament\Organization\Settings\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Services\PermissionRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make()
                    ->schema([
                        TextInput::make('label')
                            ->hiddenLabel()
                            ->prefix(__('settings.roles.form.label'))
                            ->required()
                            ->maxLength(64)
                            ->disabled(fn (?Role $record): bool => (bool) $record?->locked)
                            ->helperText(__('settings.roles.form.label_helper')),
                    ]),

                ...self::permissionSections(),
            ]);
    }

    /**
     * @return array<int, Section>
     */
    private static function permissionSections(): array
    {
        $sections = [];

        foreach (PermissionRegistry::grouped() as $resource => $permissions) {
            $sections[] = Section::make(__('permissions.resources.'.$resource))
                ->schema([
                    CheckboxList::make(RoleResource::resourceKey($resource))
                        ->hiddenLabel()
                        ->options(self::optionsFor($permissions))
                        ->columns(3)
                        ->bulkToggleable()
                        ->disabled(fn (?Role $record): bool => (bool) $record?->locked),
                ])
                ->collapsible();
        }

        return $sections;
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, string>
     */
    private static function optionsFor(array $permissions): array
    {
        $options = [];

        foreach ($permissions as $permission) {
            [$action] = self::splitPermission($permission);
            $options[$permission] = __('permissions.actions.'.$action);
        }

        return $options;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitPermission(string $name): array
    {
        $actions = PermissionRegistry::actions();
        usort($actions, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($actions as $action) {
            $prefix = $action.'_';
            if (str_starts_with($name, $prefix)) {
                return [$action, Str::after($name, $prefix)];
            }
        }

        return ['', $name];
    }
}

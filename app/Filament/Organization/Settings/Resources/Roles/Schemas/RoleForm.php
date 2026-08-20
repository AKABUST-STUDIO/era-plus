<?php

namespace App\Filament\Organization\Settings\Resources\Roles\Schemas;

use App\Models\Role;
use App\Services\PermissionRegistry;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

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

                Hidden::make('permissions')
                    ->default([]),

                View::make('filament.roles.permissions-matrix')
                    ->viewData(fn (?Role $record): array => [
                        'title' => __('settings.roles.form.permissions_heading'),
                        'groups' => PermissionRegistry::grouped(PermissionRegistry::SCOPE_ORGANIZATION),
                        'actionLabels' => self::actionLabels(),
                        'resourceLabels' => self::resourceLabels(),
                        'statePath' => 'data.permissions',
                        'disabled' => (bool) $record?->locked,
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function actionLabels(): array
    {
        $translations = trans('permissions.actions');

        return is_array($translations) ? $translations : [];
    }

    /**
     * @return array<string, string>
     */
    private static function resourceLabels(): array
    {
        $translations = trans('permissions.resources');

        return is_array($translations) ? $translations : [];
    }
}

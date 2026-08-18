<?php

namespace App\Filament\Project\Resources\ProjectEvents;

use App\Filament\Contracts\HasOrganizationPermissions;
use App\Filament\Contracts\HasProjectPermissions;
use App\Filament\Project\Resources\ProjectEvents\Pages\ListProjectEvents;
use App\Filament\Project\Resources\ProjectEvents\Schemas\ProjectEventForm;
use App\Models\Project\ProjectEvent;
use App\Services\PermissionRegistry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

class ProjectEventResource extends Resource implements HasOrganizationPermissions, HasProjectPermissions
{
    protected static ?string $model = ProjectEvent::class;

    /**
     * @return list<string>|null
     */
    public static function getPermissionActions(string $scope): ?array
    {
        return match ($scope) {
            PermissionRegistry::SCOPE_ORGANIZATION => ['view'],
            PermissionRegistry::SCOPE_PROJECT => ['view_any', 'view', 'create', 'update_any', 'delete_any'],
            default => null,
        };
    }

    protected static ?string $slug = 'events';

    protected static ?string $recordTitleAttribute = 'title';

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('events.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('events.navigation.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectEventForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectEvents::route('/'),
        ];
    }
}

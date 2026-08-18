<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Contracts\HasOrganizationPermissions;
use App\Filament\Contracts\HasProjectPermissions;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use App\Services\PermissionRegistry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProjectResource extends Resource implements HasOrganizationPermissions, HasProjectPermissions
{
    protected static ?string $model = Project::class;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * @return list<string>|null
     */
    public static function getPermissionActions(string $scope): ?array
    {
        return match ($scope) {
            PermissionRegistry::SCOPE_ORGANIZATION => ['view_any', 'create'],
            PermissionRegistry::SCOPE_PROJECT => ['update', 'delete'],
            default => null,
        };
    }

    protected static ?int $navigationSort = -2;

    public static function getNavigationLabel(): string
    {
        return __('navigation.projects');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}

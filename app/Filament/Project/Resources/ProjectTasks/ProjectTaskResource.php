<?php

namespace App\Filament\Project\Resources\ProjectTasks;

use App\Filament\Project\Resources\ProjectTasks\Pages\CreateProjectTask;
use App\Filament\Project\Resources\ProjectTasks\Pages\EditProjectTask;
use App\Filament\Project\Resources\ProjectTasks\Pages\ListProjectTasks;
use App\Filament\Project\Resources\ProjectTasks\Schemas\ProjectTaskForm;
use App\Filament\Project\Resources\ProjectTasks\Tables\ProjectTasksTable;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProjectTaskResource extends Resource
{
    protected static ?string $model = ProjectTask::class;

    public static function getNavigationLabel(): string
    {
        return __('navigation.tasks');
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ProjectTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectTasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectTasks::route('/'),
            'create' => CreateProjectTask::route('/create'),
            'edit' => EditProjectTask::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public static function canCreate(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public static function canEdit(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    public static function canDelete(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_TASKS);
    }

    private static function userCan(string $ability): bool
    {
        $project = Filament::getTenant();
        $user = auth()->user();

        if (! $project instanceof Project || $user === null) {
            return false;
        }

        return $user->can($ability, $project);
    }
}

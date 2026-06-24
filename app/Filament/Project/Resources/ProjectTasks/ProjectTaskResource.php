<?php

namespace App\Filament\Project\Resources\ProjectTasks;

use App\Filament\Project\Resources\ProjectTasks\Pages\CreateProjectTask;
use App\Filament\Project\Resources\ProjectTasks\Pages\EditProjectTask;
use App\Filament\Project\Resources\ProjectTasks\Pages\ListProjectTasks;
use App\Filament\Project\Resources\ProjectTasks\Schemas\ProjectTaskForm;
use App\Filament\Project\Resources\ProjectTasks\Tables\ProjectTasksTable;
use App\Models\ProjectTask;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProjectTaskResource extends Resource
{
    protected static ?string $model = ProjectTask::class;

    protected static ?string $navigationLabel = 'Tasks';

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
}

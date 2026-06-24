<?php

namespace App\Filament\Project\Resources\ProjectEvents;

use App\Filament\Project\Resources\ProjectEvents\Pages\CreateProjectEvent;
use App\Filament\Project\Resources\ProjectEvents\Pages\EditProjectEvent;
use App\Filament\Project\Resources\ProjectEvents\Pages\ListProjectEvents;
use App\Filament\Project\Resources\ProjectEvents\Schemas\ProjectEventForm;
use App\Filament\Project\Resources\ProjectEvents\Tables\ProjectEventsTable;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProjectEventResource extends Resource
{
    protected static ?string $model = ProjectEvent::class;

    public static function getNavigationLabel(): string
    {
        return __('navigation.activities');
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ProjectEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectEvents::route('/'),
            'create' => CreateProjectEvent::route('/create'),
            'edit' => EditProjectEvent::route('/{record}/edit'),
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

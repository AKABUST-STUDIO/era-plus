<?php

namespace App\Filament\Project\Resources\ProjectEvents;

use App\Filament\Project\Resources\ProjectEvents\Pages\CreateProjectEvent;
use App\Filament\Project\Resources\ProjectEvents\Pages\EditProjectEvent;
use App\Filament\Project\Resources\ProjectEvents\Pages\ListProjectEvents;
use App\Filament\Project\Resources\ProjectEvents\Schemas\ProjectEventForm;
use App\Filament\Project\Resources\ProjectEvents\Tables\ProjectEventsTable;
use App\Models\ProjectEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectEventResource extends Resource
{
    protected static ?string $model = ProjectEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Activities';

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
}

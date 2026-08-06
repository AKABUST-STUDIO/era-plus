<?php

namespace App\Filament\Project\Resources\ProjectParticipants;

use App\Filament\Project\Resources\ProjectParticipants\Pages\EditProjectParticipant;
use App\Filament\Project\Resources\ProjectParticipants\Pages\ListProjectParticipants;
use App\Filament\Project\Resources\ProjectParticipants\Schemas\ProjectParticipantForm;
use App\Filament\Project\Resources\ProjectParticipants\Tables\ProjectParticipantsTable;
use App\Models\Project\ProjectParticipant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class ProjectParticipantResource extends Resource
{
    protected static ?string $model = ProjectParticipant::class;

    protected static ?string $slug = 'participants';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('participable');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record->participable->name;
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.participants');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.participant');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectParticipantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectParticipantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectParticipants::route('/'),
            'edit' => EditProjectParticipant::route('/{record}/edit'),
        ];
    }
}

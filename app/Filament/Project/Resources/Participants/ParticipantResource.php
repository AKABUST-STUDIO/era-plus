<?php

namespace App\Filament\Project\Resources\Participants;

use App\Filament\Project\Resources\Participants\Pages\CreateParticipant;
use App\Filament\Project\Resources\Participants\Pages\EditParticipant;
use App\Filament\Project\Resources\Participants\Pages\ListParticipants;
use App\Filament\Project\Resources\Participants\Schemas\ParticipantForm;
use App\Filament\Project\Resources\Participants\Tables\ParticipantsTable;
use App\Models\Participant;
use App\Models\Project;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function form(Schema $schema): Schema
    {
        return ParticipantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParticipantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParticipants::route('/'),
            'create' => CreateParticipant::route('/create'),
            'edit' => EditParticipant::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public static function canCreate(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public static function canEdit(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
    }

    public static function canDelete(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_PARTICIPANTS);
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

<?php

namespace App\Filament\Project\Resources\Participants;

use App\Filament\Project\Resources\Participants\Pages\CreateParticipant;
use App\Filament\Project\Resources\Participants\Pages\EditParticipant;
use App\Filament\Project\Resources\Participants\Pages\ListParticipants;
use App\Filament\Project\Resources\Participants\Schemas\ParticipantForm;
use App\Filament\Project\Resources\Participants\Tables\ParticipantsTable;
use App\Models\Participant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

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
}

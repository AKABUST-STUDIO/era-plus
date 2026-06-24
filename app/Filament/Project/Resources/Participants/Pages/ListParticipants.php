<?php

namespace App\Filament\Project\Resources\Participants\Pages;

use App\Filament\Project\Resources\Participants\ParticipantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParticipants extends ListRecords
{
    protected static string $resource = ParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

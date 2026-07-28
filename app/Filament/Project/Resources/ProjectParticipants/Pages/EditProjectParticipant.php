<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Pages;

use App\Filament\Project\Resources\ProjectParticipants\ProjectParticipantResource;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditProjectParticipant extends EditRecord
{
    protected static string $resource = ProjectParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // protected function handleRecordUpdate(Model $record, array $data): Model
    // {
    //     return DB::transaction(function () use ($record, $data): Model {
    //         /** @var ProjectParticipant $record */
    //         $person = $record->participable;

    //         if ($person instanceof Participant) {
    //             $person->update([
    //                 'name' => $data['name'],
    //                 'email' => $data['email'] ?? null,
    //                 'phone' => $data['phone'] ?? null,
    //                 'date_of_birth' => $data['date_of_birth'] ?? null,
    //             ]);
    //         }

    //         $record->update([
    //             'country_id' => $data['country_id'],
    //             'ext_organization' => $data['ext_organization'],
    //         ]);

    //         return $record;
    //     });
    // }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Organization\Resources\Projects\Pages;

use App\Filament\Organization\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function afterCreate(): void
    {
        $this->record->users()->attach(auth()->id());
    }
}

<?php

namespace App\Filament\Project\Resources\ProjectTasks\Pages;

use App\Filament\Project\Resources\ProjectTasks\ProjectTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectTask extends EditRecord
{
    protected static string $resource = ProjectTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

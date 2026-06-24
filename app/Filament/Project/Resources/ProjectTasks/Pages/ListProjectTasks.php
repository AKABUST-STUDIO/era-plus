<?php

namespace App\Filament\Project\Resources\ProjectTasks\Pages;

use App\Filament\Project\Resources\ProjectTasks\ProjectTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectTasks extends ListRecords
{
    protected static string $resource = ProjectTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Project\Resources\ProjectEvents\Pages;

use App\Filament\Project\Resources\ProjectEvents\ProjectEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectEvents extends ListRecords
{
    protected static string $resource = ProjectEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

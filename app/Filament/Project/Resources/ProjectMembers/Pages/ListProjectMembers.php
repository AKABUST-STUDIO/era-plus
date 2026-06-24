<?php

namespace App\Filament\Project\Resources\ProjectMembers\Pages;

use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectMembers extends ListRecords
{
    protected static string $resource = ProjectMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

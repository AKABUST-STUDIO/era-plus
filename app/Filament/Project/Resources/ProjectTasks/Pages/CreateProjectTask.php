<?php

namespace App\Filament\Project\Resources\ProjectTasks\Pages;

use App\Filament\Project\Resources\ProjectTasks\ProjectTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectTask extends CreateRecord
{
    protected static string $resource = ProjectTaskResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['assigned_by'] = auth()->id();

        return $data;
    }
}

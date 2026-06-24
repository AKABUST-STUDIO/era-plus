<?php

namespace App\Filament\Project\Resources\ProjectEvents\Pages;

use App\Filament\Project\Resources\ProjectEvents\ProjectEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectEvent extends EditRecord
{
    protected static string $resource = ProjectEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

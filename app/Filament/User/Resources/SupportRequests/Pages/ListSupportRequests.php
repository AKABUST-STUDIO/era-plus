<?php

namespace App\Filament\User\Resources\SupportRequests\Pages;

use App\Filament\User\Resources\SupportRequests\Actions\CreateSupportRequestAction;
use App\Filament\User\Resources\SupportRequests\SupportRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportRequests extends ListRecords
{
    protected static string $resource = SupportRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateSupportRequestAction::make(),
        ];
    }
}

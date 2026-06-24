<?php

namespace App\Filament\Project\Resources\FinanceEntries\Pages;

use App\Filament\Project\Resources\FinanceEntries\FinanceEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinanceEntries extends ListRecords
{
    protected static string $resource = FinanceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Project\Resources\FinanceEntries\Pages;

use App\Filament\Project\Resources\FinanceEntries\FinanceEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinanceEntry extends EditRecord
{
    protected static string $resource = FinanceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

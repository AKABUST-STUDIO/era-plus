<?php

namespace App\Filament\Project\Resources\FinanceEntries\Pages;

use App\Filament\Project\Resources\FinanceEntries\FinanceEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinanceEntry extends CreateRecord
{
    protected static string $resource = FinanceEntryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}

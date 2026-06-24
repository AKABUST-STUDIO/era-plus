<?php

namespace App\Filament\Project\Resources\TravelExpenses\Pages;

use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTravelExpense extends CreateRecord
{
    protected static string $resource = TravelExpenseResource::class;

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

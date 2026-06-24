<?php

namespace App\Filament\Project\Resources\TravelExpenses\Pages;

use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTravelExpense extends EditRecord
{
    protected static string $resource = TravelExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

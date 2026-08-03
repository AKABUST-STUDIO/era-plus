<?php

namespace App\Filament\Project\Resources\TravelExpenses\Actions;

use App\Filament\Project\Resources\TravelExpenses\Exporters\TravelExpenseExporter;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Support\Enums\Alignment;

class ExportTravelExpensesAction
{
    public static function make(string $name = 'export'): ExportAction
    {
        return ExportAction::make($name)
            ->label(__('finance.actions.export'))
            ->modalIcon('lucide-download')
            ->exporter(TravelExpenseExporter::class)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->modalSubmitAction(fn (Action $action) => $action->icon('lucide-download'))
            ->extraModalWindowAttributes(['class' => 'export-modal'])
            ->fileName(fn (): string => 'travel-expenses-'.now()->format('Y-m-d'));
    }
}

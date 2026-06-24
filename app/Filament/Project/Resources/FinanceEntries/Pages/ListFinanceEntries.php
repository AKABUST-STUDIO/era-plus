<?php

namespace App\Filament\Project\Resources\FinanceEntries\Pages;

use App\Filament\Project\Resources\FinanceEntries\FinanceEntryResource;
use App\Models\Project;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListFinanceEntries extends ListRecords
{
    protected static string $resource = FinanceEntryResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return null;
        }

        $total = number_format((float) $project->financeTotal(), 2, '.', ',');

        return __('Project total: €:total', ['total' => $total]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

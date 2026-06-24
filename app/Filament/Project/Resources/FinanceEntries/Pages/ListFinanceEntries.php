<?php

namespace App\Filament\Project\Resources\FinanceEntries\Pages;

use App\Exports\AuditFinanceExport;
use App\Exports\FinanceEntriesExport;
use App\Filament\Project\Resources\FinanceEntries\FinanceEntryResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): BinaryFileResponse => $this->exportExcel()),
            Action::make('exportAudit')
                ->label('Audit-ready export')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->action(fn (): BinaryFileResponse => $this->exportAudit()),
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $project = Filament::getTenant();
        abort_unless($project instanceof Project, 404);

        $filename = sprintf('finance-%s-%s.xlsx', $project->slug, now()->format('Y-m-d'));

        return Excel::download(new FinanceEntriesExport($project), $filename);
    }

    public function exportAudit(): BinaryFileResponse
    {
        $project = Filament::getTenant();
        abort_unless($project instanceof Project, 404);

        $filename = sprintf('audit-finance-%s-%s.xlsx', $project->slug, now()->format('Y-m-d'));

        return Excel::download(new AuditFinanceExport($project), $filename);
    }
}

<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Actions;

use App\Filament\Project\Resources\ProjectParticipants\Exporters\ProjectParticipantExporter;
use App\Models\Project;
use App\Models\Project\ProjectParticipant;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Facades\Filament;
use Filament\Support\Enums\Alignment;

class ExportParticipantsAction
{
    public static function make(string $name = 'export'): ExportAction
    {
        return ExportAction::make($name)
            ->label(__('participant.actions.export'))
            ->modalIcon('lucide-download')
            ->exporter(ProjectParticipantExporter::class)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->modalSubmitAction(fn (Action $action) => $action->icon('lucide-download'))
            ->extraModalWindowAttributes(['class' => 'export-modal'])
            ->visible(function (): bool {
                $project = Filament::getTenant();

                return $project instanceof Project
                    && ProjectParticipant::query()->where('project_id', $project->id)->exists();
            })
            ->fileName(fn (): string => 'participants-'.now()->format('Y-m-d'));
    }
}

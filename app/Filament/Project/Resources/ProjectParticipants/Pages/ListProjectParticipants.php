<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Pages;

use App\Filament\Project\Resources\ProjectParticipants\Actions\AddParticipantAction;
use App\Filament\Project\Resources\ProjectParticipants\Actions\ExportParticipantsAction;
use App\Filament\Project\Resources\ProjectParticipants\Actions\ImportParticipantsAction;
use App\Filament\Project\Resources\ProjectParticipants\ProjectParticipantResource;
use App\Filament\Project\Resources\ProjectParticipants\Tables\ProjectParticipantsTable;
use App\Models\Project;
use App\Models\Project\ProjectParticipant;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListProjectParticipants extends ListRecords
{
    protected static string $resource = ProjectParticipantResource::class;

    #[On('participants::refresh-tabs')]
    public function refreshTabs(): void
    {
        unset($this->cachedTabs);
    }

    protected function getHeaderActions(): array
    {
        return [
            AddParticipantAction::make(),
            ActionGroup::make([
                ImportParticipantsAction::make(),
                ExportParticipantsAction::make(),
            ])
                ->label(__('participant.actions.tools'))
                ->icon('lucide-more-horizontal')
                ->button()
                ->color('gray'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $project = Filament::getTenant();
        $totalCount = $project instanceof Project
            ? ProjectParticipant::query()->where('project_id', $project->id)->count()
            : 0;

        $tabs = [
            'all' => Tab::make(__('participant.tabs.all'))
                ->badge($totalCount),
        ];

        if (! $project instanceof Project) {
            return $tabs;
        }

        $countries = ProjectParticipant::query()
            ->where('project_id', $project->id)
            ->whereNotNull('country_id')
            ->with('country')
            ->get()
            ->map(fn (ProjectParticipant $row) => $row->country)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        foreach ($countries as $country) {
            $tabs['country-'.$country->id] = Tab::make($country->name)
                ->icon(ProjectParticipantsTable::countryIcon($country->iso2))
                ->badge(fn (): int => ProjectParticipant::query()
                    ->where('project_id', $project->id)
                    ->where('country_id', $country->id)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('country_id', $country->id));
        }

        return $tabs;
    }
}

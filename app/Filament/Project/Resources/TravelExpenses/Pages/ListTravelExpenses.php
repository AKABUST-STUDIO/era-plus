<?php

namespace App\Filament\Project\Resources\TravelExpenses\Pages;

use App\Facades\ProjectService;
use App\Filament\Project\Resources\ProjectParticipants\Tables\ProjectParticipantsTable;
use App\Filament\Project\Resources\TravelExpenses\Actions\CountryLimitsAction;
use App\Filament\Project\Resources\TravelExpenses\Actions\ExportTravelExpensesAction;
use App\Filament\Project\Resources\TravelExpenses\Actions\ImportTravelExpensesAction;
use App\Filament\Project\Resources\TravelExpenses\Schemas\TravelExpenseForm;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Models\Project;
use App\Models\Project\CountryLimit;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Nnjeim\World\Models\Country;

class ListTravelExpenses extends ListRecords
{
    protected static string $resource = TravelExpenseResource::class;

    public function getTitle(): string
    {
        return __('finance.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('finance.actions.add'))
                ->icon('lucide-plus')
                ->modalIcon('lucide-receipt-euro')
                ->modalHeading(false)
                ->modalWidth(Width::Large)
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->modalFooterActionsAlignment(Alignment::End)
                ->modalSubmitActionLabel(__('finance.add.submit'))
                ->steps(TravelExpenseForm::steps())
                ->modifyWizardUsing(fn (Wizard $wizard): Wizard => $wizard
                    ->hiddenHeader()
                    ->submitAction(view('filament.finance.wizard-submit')))
                ->mutateDataUsing(function (array $data): array {
                    $data['project_participant_id'] = TravelExpenseResource::canViewAllExpenses()
                        ? TravelExpenseForm::resolveParticipationId($data)
                        : ProjectService::participationIdFor();

                    return TravelExpenseForm::stripParticipableInput($data);
                }),
            CountryLimitsAction::make()
                ->authorize(fn (): bool => (bool) Filament::auth()->user()?->can('update', CountryLimit::class)),
            ActionGroup::make([
                ImportTravelExpensesAction::make()
                    ->authorize(fn (): bool => TravelExpenseResource::canImport()),
                ExportTravelExpensesAction::make()
                    ->authorize(fn (): bool => TravelExpenseResource::canExport()),
            ])
                ->label(__('finance.actions.tools'))
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
        $ownParticipationId = TravelExpenseResource::canViewAllExpenses()
            ? null
            : (ProjectService::participationIdFor() ?? 0);

        $totalCount = $project instanceof Project
            ? TravelExpense::query()
                ->when($ownParticipationId !== null, fn (Builder $query): Builder => $query->where('project_participant_id', $ownParticipationId))
                ->count()
            : 0;

        $tabs = [
            'all' => Tab::make(__('finance.tabs.all'))
                ->badge($totalCount),
        ];

        if (! $project instanceof Project || $ownParticipationId !== null) {
            return $tabs;
        }

        $countries = Country::query()
            ->whereIn('id', ProjectParticipant::query()
                ->where('project_id', $project->id)
                ->whereNotNull('country_id')
                ->select('country_id'))
            ->orderBy('name')
            ->get();

        foreach ($countries as $country) {
            $tabs['country-'.$country->id] = Tab::make($country->name)
                ->icon(ProjectParticipantsTable::countryIcon($country->iso2))
                ->badge(fn (): int => TravelExpense::query()
                    ->whereRelation('projectParticipant', 'country_id', $country->id)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereRelation('projectParticipant', 'country_id', $country->id));
        }

        return $tabs;
    }
}

<?php

namespace App\Filament\Project\Resources\TravelExpenses\Pages;

use App\Exports\TravelExpensesExport;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Models\Project;
use App\Models\ProjectCountry;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListTravelExpenses extends ListRecords
{
    protected static string $resource = TravelExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('editCountryLimits')
                ->label('Country limits')
                ->icon('lucide-globe')
                ->modalHeading('Per-country default travel expense limits')
                ->fillForm(fn (): array => $this->countryLimitsForm())
                ->form(fn (): array => $this->countryLimitFields())
                ->action(fn (array $data) => $this->saveCountryLimits($data)),
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('lucide-download')
                ->action(fn (): BinaryFileResponse => $this->exportExcel()),
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $project = Filament::getTenant();
        abort_unless($project instanceof Project, 404);

        $filename = sprintf('travel-%s-%s.xlsx', $project->slug, now()->format('Y-m-d'));

        return Excel::download(new TravelExpensesExport($project), $filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function countryLimitsForm(): array
    {
        $project = Filament::getTenant();
        abort_unless($project instanceof Project, 404);

        $values = [];
        foreach ($project->countries()->with('country')->get() as $pc) {
            $values['limit_'.$pc->country_id] = $pc->default_travel_expense_limit;
        }

        return $values;
    }

    /**
     * @return array<int, TextInput>
     */
    private function countryLimitFields(): array
    {
        $project = Filament::getTenant();
        abort_unless($project instanceof Project, 404);

        $fields = [];
        foreach ($project->countries()->with('country')->get() as $pc) {
            $fields[] = TextInput::make('limit_'.$pc->country_id)
                ->label($pc->country->name)
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->prefix('€');
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveCountryLimits(array $data): void
    {
        $project = Filament::getTenant();
        abort_unless($project instanceof Project, 404);

        foreach ($project->countries as $pc) {
            $key = 'limit_'.$pc->country_id;
            if (! array_key_exists($key, $data)) {
                continue;
            }

            ProjectCountry::query()
                ->where('id', $pc->id)
                ->update(['default_travel_expense_limit' => $data[$key]]);
        }

        Notification::make()
            ->title('Country travel limits updated')
            ->success()
            ->send();
    }
}

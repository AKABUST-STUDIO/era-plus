<?php

namespace App\Filament\Project\Resources\TravelExpenses\Actions;

use App\Filament\Project\Resources\ProjectParticipants\Tables\ProjectParticipantsTable;
use App\Livewire\Finance\Reimbursement;
use App\Models\Project;
use App\Models\Project\CountryLimit;
use App\Models\Project\ProjectParticipant;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component as LivewireComponent;
use Nnjeim\World\Models\Country;

class CountryLimitsAction
{
    public static function make(string $name = 'countryLimits'): Action
    {
        return Action::make($name)
            ->label(__('finance.actions.limits'))
            ->icon('lucide-gauge')
            ->color('gray')
            ->modalIcon('lucide-gauge')
            ->modalHeading(__('finance.limits.heading'))
            ->modalDescription(__('finance.limits.description'))
            ->modalWidth(Width::Large)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalSubmitActionLabel(__('finance.limits.submit'))
            ->schema(fn (): array => self::fields())
            ->fillForm(fn (): array => ['limits' => self::currentLimits()])
            ->action(function (array $data): void {
                $project = Filament::getTenant();

                if (! $project instanceof Project) {
                    return;
                }

                foreach ($data['limits'] ?? [] as $countryId => $amount) {
                    $query = CountryLimit::query()
                        ->where('project_id', $project->id)
                        ->where('country_id', $countryId);

                    if (blank($amount)) {
                        $query->delete();

                        continue;
                    }

                    CountryLimit::updateOrCreate(
                        ['project_id' => $project->id, 'country_id' => $countryId],
                        ['amount_eur' => $amount],
                    );
                }

                Notification::make()
                    ->title(__('finance.limits.saved_title'))
                    ->success()
                    ->send();
            })
            ->after(fn (LivewireComponent $livewire) => $livewire->dispatch(Reimbursement::LIMITS_UPDATED));
    }

    /**
     * @return array<int, Component>
     */
    private static function fields(): array
    {
        $countries = self::countries();

        if ($countries->isEmpty()) {
            return [Text::make(__('finance.limits.empty'))];
        }

        return $countries
            ->map(fn (Country $country): Component => TextInput::make('limits.'.$country->id)
                ->label($country->name)
                ->prefixIcon(ProjectParticipantsTable::countryIcon($country->iso2))
                ->suffix(__('forms.common.currency_prefix'))
                ->placeholder(__('finance.limits.no_limit'))
                ->numeric()
                ->minValue(0))
            ->all();
    }

    /**
     * @return Collection<int, Country>
     */
    private static function countries(): Collection
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return new Collection;
        }

        return Country::query()
            ->whereIn('id', ProjectParticipant::query()
                ->where('project_id', $project->id)
                ->whereNotNull('country_id')
                ->select('country_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private static function currentLimits(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        return CountryLimit::query()
            ->where('project_id', $project->id)
            ->pluck('amount_eur', 'country_id')
            ->all();
    }
}

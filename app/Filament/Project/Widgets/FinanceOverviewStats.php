<?php

namespace App\Filament\Project\Widgets;

use App\Enums\BudgetCategory;
use App\Enums\FinanceOperation;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        $balance = $project->financeTotal();
        $byCategory = $project->financeTotalsByCategory();

        $expenses = $this->sumExpenses($project);
        $income = $this->sumIncome($project);
        $participantCount = $project->participants()->count();
        $countryCount = $project->countries()->count();

        return [
            Stat::make(__('forms.finance.stats.balance'), $this->formatEuro($balance))
                ->color(bccomp($balance, '0', 2) >= 0 ? 'success' : 'danger')
                ->description(__('forms.finance.stats.balance_description')),
            Stat::make(__('forms.finance.stats.income'), $this->formatEuro($income))
                ->color('success')
                ->description(__('forms.finance.stats.income_description')),
            Stat::make(__('forms.finance.stats.expenses'), $this->formatEuro($expenses))
                ->color('danger')
                ->description($this->categorySummary($byCategory)),
            Stat::make(__('forms.finance.stats.participants'), (string) $participantCount)
                ->color('info')
                ->description(trans_choice('forms.finance.stats.countries', $countryCount)),
        ];
    }

    private function sumExpenses(Project $project): string
    {
        return (string) $project->financeEntries()
            ->where('operation', FinanceOperation::Subtract->value)
            ->sum('amount');
    }

    private function sumIncome(Project $project): string
    {
        return (string) $project->financeEntries()
            ->where('operation', FinanceOperation::Add->value)
            ->sum('amount');
    }

    private function formatEuro(string|int|float $amount): string
    {
        return '€'.number_format((float) $amount, 2, '.', ',');
    }

    /**
     * @param  array<string, string>  $byCategory
     */
    private function categorySummary(array $byCategory): string
    {
        $tops = collect($byCategory)
            ->filter(fn (string $value) => bccomp($value, '0', 2) !== 0)
            ->sortBy(fn (string $value) => (float) $value)
            ->take(3);

        if ($tops->isEmpty()) {
            return __('forms.finance.stats.no_categorised_spend');
        }

        return $tops->map(function (string $value, string $key): string {
            $label = BudgetCategory::from($key)->getLabel();

            return $label.' '.$this->formatEuro($value);
        })->implode(' · ');
    }
}

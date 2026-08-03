<?php

namespace App\Livewire\Finance;

use App\Models\Project\TravelExpense;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Reimbursement extends Component
{
    public const LIMITS_UPDATED = 'finance::limits-updated';

    #[Locked]
    public int $expenseId;

    #[Locked]
    public ?string $limit = null;

    #[Locked]
    public float $spent = 0.0;

    public function mount(TravelExpense $expense): void
    {
        $participation = $expense->projectParticipant;

        $this->expenseId = $expense->getKey();
        $this->limit = $expense->countryLimit?->amount_eur;
        $this->spent = (float) ($participation?->travel_expenses_sum_cost_eur
            ?? $participation?->travelExpenses()->sum('cost_eur')
            ?? 0);
    }

    #[On(self::LIMITS_UPDATED)]
    public function recalculate(): void
    {
        $expense = TravelExpense::withoutGlobalScopes()
            ->with(['countryLimit', 'projectParticipant'])
            ->find($this->expenseId);

        if ($expense !== null) {
            $this->mount($expense);
        }
    }

    public function isOverLimit(): bool
    {
        return $this->limit !== null && $this->spent > (float) $this->limit;
    }

    public function color(): string
    {
        return match (true) {
            $this->limit === null => 'gray',
            $this->isOverLimit() => 'danger',
            default => 'success',
        };
    }

    public function icon(): ?string
    {
        return $this->isOverLimit() ? 'lucide-triangle-alert' : null;
    }

    public function label(): string
    {
        if ($this->limit === null) {
            return __('finance.limits.no_limit_set');
        }

        return __('finance.limits.spent_of', [
            'spent' => $this->euro($this->spent),
            'limit' => $this->euro((float) $this->limit),
        ]);
    }

    public function render(): View
    {
        return view('livewire.finance.reimbursement', [
            'color' => $this->color(),
            'icon' => $this->icon(),
            'label' => $this->label(),
        ]);
    }

    private function euro(float $amount): string
    {
        return __('forms.common.currency_prefix').number_format($amount, 2);
    }
}

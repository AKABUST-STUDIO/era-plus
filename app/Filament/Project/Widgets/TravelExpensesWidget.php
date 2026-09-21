<?php

namespace App\Filament\Project\Widgets;

use App\Filament\Project\Resources\ProjectParticipants\ProjectParticipantResource;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use Filament\Widgets\Widget;

class TravelExpensesWidget extends Widget
{
    protected string $view = 'filament.project.widgets.travel-expenses-widget';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return TravelExpenseResource::canViewAny();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $viewAllUrl = TravelExpenseResource::canViewAny() ? TravelExpenseResource::getUrl() : null;

        $participants = ProjectParticipantResource::getEloquentQuery()
            ->withSum('travelExpenses as total_eur', 'cost_eur')
            ->with(['country'])
            ->having('total_eur', '>', 0)
            ->orderByDesc('total_eur')
            ->limit(5)
            ->get();

        if ($participants->isEmpty()) {
            return $this->emptyPayload($viewAllUrl);
        }

        $rows = $participants
            ->map(fn (ProjectParticipant $participation): array => [
                'participation_id' => $participation->id,
                'name' => $participation->participable?->name ?? '—',
                'iso2' => strtolower($participation->country?->iso2 ?? ''),
                'total_eur' => (float) $participation->total_eur,
            ])
            ->all();

        $participantCount = ProjectParticipantResource::getEloquentQuery()
            ->whereHas('travelExpenses')
            ->count();

        $grandTotal = (float) TravelExpense::query()
            ->whereIn(
                'project_participant_id',
                ProjectParticipantResource::getEloquentQuery()->select('id'),
            )
            ->sum('cost_eur');

        return [
            'heading' => __('dashboard.project.travel_expenses_heading'),
            'subtitle' => trans_choice(
                'dashboard.project.travel_expenses_subtitle',
                $participantCount,
                ['count' => $participantCount],
            ),
            'emptyMessage' => __('dashboard.project.travel_expenses_empty_body'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'sumLabel' => __('dashboard.project.travel_expenses_sum'),
            'rows' => $rows,
            'total' => $grandTotal,
            'isEmpty' => false,
            'viewAllUrl' => $viewAllUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(?string $viewAllUrl): array
    {
        return [
            'heading' => __('dashboard.project.travel_expenses_heading'),
            'subtitle' => __('dashboard.project.travel_expenses_empty_subtitle'),
            'emptyMessage' => __('dashboard.project.travel_expenses_empty_body'),
            'viewAllLabel' => __('dashboard.common.view_all'),
            'sumLabel' => __('dashboard.project.travel_expenses_sum'),
            'rows' => [],
            'total' => 0.0,
            'isEmpty' => true,
            'viewAllUrl' => $viewAllUrl,
        ];
    }
}

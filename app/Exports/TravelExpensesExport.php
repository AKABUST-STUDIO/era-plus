<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\TravelExpense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class TravelExpensesExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Project $project) {}

    /**
     * @return Collection<int, TravelExpense>
     */
    public function collection(): Collection
    {
        return TravelExpense::query()
            ->where('project_id', $this->project->id)
            ->with(['participant.country', 'createdBy'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Date',
            'Participant',
            'Country',
            'Amount',
            'Over country limit?',
            'Description',
            'Created by',
            'Created at',
        ];
    }

    /**
     * @param  TravelExpense  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            $row->occurred_at->toDateString(),
            $row->participant?->full_name,
            $row->participant?->country?->name,
            number_format((float) $row->amount, 2, '.', ''),
            $row->exceedsCountryLimit() ? 'YES' : 'no',
            $row->description,
            $row->createdBy?->name,
            $row->created_at?->toIso8601String(),
        ];
    }

    public function title(): string
    {
        return 'Travel';
    }
}

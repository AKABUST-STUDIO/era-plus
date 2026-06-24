<?php

namespace App\Exports;

use App\Models\FinanceEntry;
use App\Models\Project;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinanceEntriesExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Project $project) {}

    /**
     * @return Collection<int, FinanceEntry>
     */
    public function collection(): Collection
    {
        return FinanceEntry::query()
            ->where('project_id', $this->project->id)
            ->with('createdBy')
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
            'Operation',
            'Amount',
            'Signed amount',
            'Description',
            'Created by',
            'Created at',
        ];
    }

    /**
     * @param  FinanceEntry  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            $row->occurred_at->toDateString(),
            $row->operation->getLabel(),
            number_format((float) $row->amount, 2, '.', ''),
            $row->signedAmount(),
            $row->description,
            $row->createdBy?->name,
            $row->created_at?->toIso8601String(),
        ];
    }

    public function title(): string
    {
        return 'Finance';
    }
}

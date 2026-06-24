<?php

namespace App\Exports;

use App\Models\FinanceEntry;
use App\Models\Project;
use App\Models\TravelExpense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class AuditFinanceExport implements WithMultipleSheets
{
    public function __construct(private readonly Project $project) {}

    /**
     * @return array<int, FromCollection&WithTitle>
     */
    public function sheets(): array
    {
        return [
            new AuditFinanceEntriesSheet($this->project),
            new AuditCategoryTotalsSheet($this->project),
            new AuditTravelExpensesSheet($this->project),
            new AuditDocumentReferencesSheet($this->project),
        ];
    }
}

class AuditFinanceEntriesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Project $project) {}

    public function collection(): Collection
    {
        return FinanceEntry::query()
            ->where('project_id', $this->project->id)
            ->with(['createdBy', 'media'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Entry ID',
            'Date',
            'Category',
            'Operation',
            'Amount',
            'Signed amount',
            'Description',
            'Created by',
            'Created at',
            'Updated at',
            'Document count',
            'Flagged?',
        ];
    }

    /**
     * @param  FinanceEntry  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            (string) $row->id,
            $row->occurred_at->toDateString(),
            $row->cost_category?->getLabel(),
            $row->operation->getLabel(),
            number_format((float) $row->amount, 2, '.', ''),
            $row->signedAmount(),
            $row->description,
            $row->createdBy?->name,
            $row->created_at?->toIso8601String(),
            $row->updated_at?->toIso8601String(),
            (string) $row->getMedia(FinanceEntry::DOCUMENTS_COLLECTION)->count(),
            $row->isFlagged() ? 'YES' : 'no',
        ];
    }

    public function title(): string
    {
        return 'Entries';
    }
}

class AuditCategoryTotalsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Project $project) {}

    public function collection(): Collection
    {
        return collect($this->project->financeTotalsByCategory());
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Category', 'Signed total (EUR)'];
    }

    /**
     * @param  string  $row
     * @return array<int, string>
     */
    public function map($row): array
    {
        $key = (string) $this->collection()->search($row);

        return [
            \App\Enums\BudgetCategory::from($key)->getLabel(),
            $row,
        ];
    }

    public function title(): string
    {
        return 'By category';
    }
}

class AuditTravelExpensesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Project $project) {}

    public function collection(): Collection
    {
        return TravelExpense::query()
            ->where('project_id', $this->project->id)
            ->with(['participant.country', 'createdBy'])
            ->orderBy('occurred_at')
            ->get();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Travel ID',
            'Date',
            'Participant',
            'Country',
            'Amount',
            'Over country limit?',
            'Description',
            'Created by',
        ];
    }

    /**
     * @param  TravelExpense  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            (string) $row->id,
            $row->occurred_at->toDateString(),
            $row->participant?->full_name,
            $row->participant?->country?->name,
            number_format((float) $row->amount, 2, '.', ''),
            $row->exceedsCountryLimit() ? 'YES' : 'no',
            $row->description,
            $row->createdBy?->name,
        ];
    }

    public function title(): string
    {
        return 'Travel';
    }
}

class AuditDocumentReferencesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Project $project) {}

    public function collection(): Collection
    {
        $entries = FinanceEntry::query()
            ->where('project_id', $this->project->id)
            ->with('media')
            ->get();

        $rows = collect();

        foreach ($entries as $entry) {
            foreach ($entry->getMedia(FinanceEntry::DOCUMENTS_COLLECTION) as $media) {
                $rows->push((object) [
                    'subject_type' => 'FinanceEntry',
                    'subject_id' => $entry->id,
                    'subject_date' => $entry->occurred_at->toDateString(),
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size_bytes' => $media->size,
                    'uploaded_at' => $media->created_at?->toIso8601String(),
                ]);
            }
        }

        return $rows;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Subject type',
            'Subject ID',
            'Subject date',
            'File name',
            'MIME type',
            'Size (bytes)',
            'Uploaded at',
        ];
    }

    /**
     * @param  object  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            $row->subject_type,
            (string) $row->subject_id,
            $row->subject_date,
            $row->file_name,
            $row->mime_type,
            (string) $row->size_bytes,
            $row->uploaded_at,
        ];
    }

    public function title(): string
    {
        return 'Documents';
    }
}

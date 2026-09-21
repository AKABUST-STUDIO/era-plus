<?php

namespace App\Filament\Tables;

use App\Filament\Tables\Filters\DateFilter;
use App\Filament\Tables\Filters\FilterGroup;
use App\Filament\Tables\Filters\SearchFilter;
use App\Models\ActivityLog;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogTable
{
    public static function configure(Table $table, Builder $query): Table
    {
        return $table
            ->extraAttributes(['class' => 'fi-ta-activity'])
            ->query(fn (): Builder => $query->with(['causer', 'subject']))
            ->paginated(fn (HasTable $livewire): bool => ($livewire->getFilteredTableQuery()?->count() ?? 0) > 50)
            ->paginationMode(PaginationMode::Simple)
            ->paginationPageOptions([50])
            ->defaultPaginationPageOption(50)
            ->defaultSort('created_at', 'desc')
            ->defaultGroup(
                Group::make('month')
                    ->label(false)
                    ->getKeyFromRecordUsing(fn (ActivityLog $log): string => $log->created_at?->format('Y-m') ?? '')
                    ->getTitleFromRecordUsing(fn (ActivityLog $log): string => $log->created_at?->translatedFormat('F Y') ?? '—')
                    ->orderQueryUsing(fn (Builder $q, string $direction): Builder => $q->orderBy('created_at', $direction)),
            )
            ->columns([
                ViewColumn::make('row')
                    ->label('')
                    ->view('filament.tables.columns.activity-log-row'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->searchable(false)
            ->hiddenFilterIndicators(true)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->columnManager(false)
            ->groupingSettingsHidden()
            ->filtersFormColumns(3)
            ->filters([
                SearchFilter::make()
                    ->columnStart(2),
                FilterGroup::make(
                    schema: [
                        Grid::make(2)->schema([
                            DateFilter::make('from', __('activity.filters.from'), 'lucide-calendar-arrow-up'),
                            DateFilter::make('until', __('activity.filters.until'), 'lucide-calendar-arrow-down'),
                        ]),
                    ],
                    query: fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)),
                    width: Width::Medium)
                    ->columnStart(3),
            ]);
    }
}

<?php

namespace App\Filament\Project\Resources\FinanceEntries\Tables;

use App\Enums\BudgetCategory;
use App\Enums\FinanceOperation;
use App\Models\FinanceEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class FinanceEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->groups([
                Group::make('cost_category')
                    ->label(__('forms.common.category'))
                    ->getTitleFromRecordUsing(fn (FinanceEntry $r): string => $r->cost_category?->getLabel() ?? 'Uncategorised'),
                Group::make('operation')
                    ->label(__('forms.finance.operation'))
                    ->getTitleFromRecordUsing(fn (FinanceEntry $r): string => $r->operation->getLabel()),
            ])
            ->columns([
                TextColumn::make('occurred_at')
                    ->label(__('forms.common.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('operation')
                    ->badge()
                    ->sortable(),
                TextColumn::make('cost_category')
                    ->label(__('forms.common.category'))
                    ->badge()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable()
                    ->summarize(Sum::make()->money('EUR')->label(__('forms.finance.total'))),
                IconColumn::make('flagged')
                    ->label(__('forms.common.flagged'))
                    ->state(fn (FinanceEntry $record): bool => $record->isFlagged())
                    ->icon('lucide-triangle-alert')
                    ->color('danger')
                    ->trueIcon('lucide-triangle-alert')
                    ->falseIcon(null)
                    ->tooltip(fn (FinanceEntry $record): ?string => $record->isFlagged()
                        ? __('forms.finance.flag_tooltip')
                        : null),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('operation')
                    ->options(FinanceOperation::class),
                SelectFilter::make('cost_category')
                    ->label(__('forms.common.category'))
                    ->options(BudgetCategory::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

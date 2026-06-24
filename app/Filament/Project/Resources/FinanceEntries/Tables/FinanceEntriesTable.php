<?php

namespace App\Filament\Project\Resources\FinanceEntries\Tables;

use App\Enums\BudgetCategory;
use App\Enums\FinanceOperation;
use App\Models\FinanceEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinanceEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('operation')
                    ->badge()
                    ->sortable(),
                TextColumn::make('cost_category')
                    ->label('Category')
                    ->badge()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable(),
                IconColumn::make('flagged')
                    ->label('Flag')
                    ->state(fn (FinanceEntry $record): bool => $record->isFlagged())
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('danger')
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)
                    ->falseIcon(null)
                    ->tooltip(fn (FinanceEntry $record): ?string => $record->isFlagged()
                        ? 'Subtract entry missing a category'
                        : null),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('operation')
                    ->options(FinanceOperation::class),
                SelectFilter::make('cost_category')
                    ->label('Category')
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

<?php

namespace App\Filament\Project\Resources\TravelExpenses\Tables;

use App\Models\TravelExpense;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TravelExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('participant.country'))
            ->defaultSort('occurred_at', 'desc')
            ->defaultGroup(
                Group::make('participant.country.name')
                    ->label('Country')
                    ->titlePrefixedWithLabel(false),
            )
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('participant.full_name')
                    ->label('Participant')
                    ->searchable(['participants.first_name', 'participants.last_name']),
                TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable()
                    ->summarize(Sum::make()->money('EUR')->label('Total')),
                IconColumn::make('over_limit')
                    ->label('Over limit')
                    ->state(fn (TravelExpense $record): bool => $record->exceedsCountryLimit())
                    ->trueIcon('lucide-triangle-alert')
                    ->falseIcon(null)
                    ->color('danger'),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
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

<?php

namespace App\Filament\Project\Resources\ProjectTasks\Tables;

use App\Enums\ProjectTaskStatus;
use App\Models\ProjectTask;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('assignedTo'))
            ->defaultGroup(
                Group::make('assignedTo.name')
                    ->label('Assigned to')
                    ->titlePrefixedWithLabel(false),
            )
            ->defaultSort('due_date')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignedTo.name')
                    ->label('Assigned to')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date()
                    ->sortable(),
                IconColumn::make('overdue')
                    ->label('Overdue')
                    ->state(fn (ProjectTask $r): bool => $r->isOverdue())
                    ->trueIcon('lucide-triangle-alert')
                    ->falseIcon(null)
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('status')->options(ProjectTaskStatus::class),
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

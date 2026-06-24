<?php

namespace App\Filament\User\Pages;

use App\Models\ActivityLog;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Activity extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.user.pages.activity';

    public function getTitle(): string
    {
        return __('user.activity.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityLog::query()
                ->causedBy(auth()->user())
                ->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('forms.common.when'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('event')
                    ->label(__('forms.common.type'))
                    ->badge(),
                TextColumn::make('description')
                    ->label(__('user.activity.action'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label(__('user.activity.subject'))
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? class_basename($state)
                        : '—')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('last_7_days')
                    ->label(__('user.activity.last_7_days'))
                    ->query(fn (Builder $q): Builder => $q->where('created_at', '>=', now()->subDays(7))),
                Filter::make('last_30_days')
                    ->label(__('user.activity.last_30_days'))
                    ->query(fn (Builder $q): Builder => $q->where('created_at', '>=', now()->subDays(30))),
            ]);
    }
}

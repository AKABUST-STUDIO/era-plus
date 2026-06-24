<?php

namespace App\Filament\Project\Resources\ProjectEvents\Tables;

use App\Models\ProjectEvent;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ProjectEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')
            ->defaultGroup(
                Group::make('starts_at')
                    ->date()
                    ->getTitleFromRecordUsing(fn (ProjectEvent $r): string => $r->starts_at?->format('F j, Y') ?? '—')
            )
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->dateTime()->toggleable(),
                IconColumn::make('all_day')->boolean(),
                TextColumn::make('location')->searchable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('share_to_google')
                    ->label('Google Calendar')
                    ->icon('lucide-calendar')
                    ->openUrlInNewTab()
                    ->url(fn (ProjectEvent $r): string => $r->googleCalendarUrl()),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

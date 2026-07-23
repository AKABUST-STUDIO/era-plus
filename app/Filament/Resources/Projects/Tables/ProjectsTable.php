<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ProjectStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('erasmus_action')
                    ->label(__('forms.project.fields.erasmus_action'))
                    ->badge()
                    ->tooltip(fn (ErasmusActionType $state): string => $state->getDescription())
                    ->sortable(),
                TextColumn::make('erasmus_field')
                    ->label(__('forms.project.fields.erasmus_field'))
                    ->badge()
                    ->icon(fn (ErasmusField $state): string => $state->getIcon())
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('forms.project.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('call_year')
                    ->label(__('forms.project.fields.call_year'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('erasmus_field')
                    ->label(__('forms.project.fields.erasmus_field'))
                    ->options(ErasmusField::options()),
                SelectFilter::make('erasmus_action')
                    ->label(__('forms.project.fields.erasmus_action'))
                    ->options(fn (): array => ErasmusActionType::optionsFor(null, null)),
                SelectFilter::make('status')
                    ->label(__('forms.project.fields.status'))
                    ->options(ProjectStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

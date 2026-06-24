<?php

namespace App\Filament\Project\Resources\Participants\Tables;

use App\Models\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParticipantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('country'))
            ->defaultGroup(
                Group::make('country.name')
                    ->label('Country')
                    ->titlePrefixedWithLabel(false),
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('country.name')
                    ->label('Country')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label('Country')
                    ->options(fn (): array => self::countryFilterOptions()),
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

    /**
     * @return array<int, string>
     */
    private static function countryFilterOptions(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        return $project->countries()
            ->with('country')
            ->get()
            ->mapWithKeys(fn ($pc) => [$pc->country_id => $pc->country->name])
            ->all();
    }
}

<?php

namespace App\Filament\Project\Resources\TravelExpenses\Schemas;

use App\Models\Participant;
use App\Models\Project;
use App\Models\TravelExpense;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Arrayable;

class TravelExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('participant_id')
                    ->label('Participant')
                    ->options(fn (): Arrayable => self::participantOptions())
                    ->searchable()
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix('€'),
                DatePicker::make('occurred_at')
                    ->label('Date')
                    ->required()
                    ->default(now()),
                Textarea::make('description')
                    ->maxLength(255)
                    ->rows(2)
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('documents')
                    ->label('Receipts / tickets')
                    ->collection(TravelExpense::DOCUMENTS_COLLECTION)
                    ->multiple()
                    ->reorderable()
                    ->openable()
                    ->downloadable()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(20480)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Arrayable<int, string>
     */
    private static function participantOptions(): Arrayable
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return collect();
        }

        return Participant::query()
            ->where('project_id', $project->id)
            ->with('country')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (Participant $p): array => [
                $p->id => sprintf('%s — %s', $p->full_name, $p->country?->name ?? '—'),
            ]);
    }
}

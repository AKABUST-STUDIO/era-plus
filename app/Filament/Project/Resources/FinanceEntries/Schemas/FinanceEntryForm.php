<?php

namespace App\Filament\Project\Resources\FinanceEntries\Schemas;

use App\Enums\BudgetCategory;
use App\Enums\FinanceOperation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FinanceEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('operation')
                    ->options(FinanceOperation::class)
                    ->default(FinanceOperation::Add)
                    ->live()
                    ->required(),
                Select::make('cost_category')
                    ->label('EU cost category')
                    ->options(BudgetCategory::class)
                    ->required(fn (callable $get): bool => $get('operation') === FinanceOperation::Subtract->value
                        || $get('operation') === FinanceOperation::Subtract)
                    ->helperText('Required for expenses (Subtract). Optional for grant top-ups (Add).'),
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
            ]);
    }
}

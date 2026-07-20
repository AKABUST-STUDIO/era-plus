<?php

namespace App\Filament\Project\Resources\FinanceEntries\Schemas;

use App\Enums\BudgetCategory;
use App\Enums\FinanceOperation;
use App\Models\FinanceEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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
                    ->label(__('forms.finance.category'))
                    ->options(BudgetCategory::class)
                    ->required(fn (callable $get): bool => $get('operation') === FinanceOperation::Subtract->value
                        || $get('operation') === FinanceOperation::Subtract)
                    ->helperText(__('forms.finance.category_helper')),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix(__('forms.common.currency_prefix')),
                DatePicker::make('occurred_at')
                    ->label(__('forms.common.date'))
                    ->required()
                    ->default(now()),
                Textarea::make('description')
                    ->maxLength(255)
                    ->rows(2)
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('documents')
                    ->label(__('forms.finance.documents'))
                    ->helperText(__('forms.finance.documents_helper'))
                    ->collection(FinanceEntry::DOCUMENTS_COLLECTION)
                    ->multiple()
                    ->reorderable()
                    ->openable()
                    ->downloadable()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(20480)
                    ->columnSpanFull(),
            ]);
    }
}

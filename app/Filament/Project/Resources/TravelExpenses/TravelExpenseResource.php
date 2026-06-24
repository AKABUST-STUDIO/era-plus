<?php

namespace App\Filament\Project\Resources\TravelExpenses;

use App\Filament\Project\Resources\TravelExpenses\Pages\CreateTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\EditTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\ListTravelExpenses;
use App\Filament\Project\Resources\TravelExpenses\Schemas\TravelExpenseForm;
use App\Filament\Project\Resources\TravelExpenses\Tables\TravelExpensesTable;
use App\Models\TravelExpense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TravelExpenseResource extends Resource
{
    protected static ?string $model = TravelExpense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Travel';

    protected static ?string $modelLabel = 'travel expense';

    protected static ?string $pluralModelLabel = 'travel expenses';

    public static function form(Schema $schema): Schema
    {
        return TravelExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelExpensesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTravelExpenses::route('/'),
            'create' => CreateTravelExpense::route('/create'),
            'edit' => EditTravelExpense::route('/{record}/edit'),
        ];
    }
}

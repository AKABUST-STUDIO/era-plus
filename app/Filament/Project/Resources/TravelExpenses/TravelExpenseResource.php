<?php

namespace App\Filament\Project\Resources\TravelExpenses;

use App\Filament\Project\Resources\TravelExpenses\Pages\CreateTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\EditTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\ListTravelExpenses;
use App\Filament\Project\Resources\TravelExpenses\Schemas\TravelExpenseForm;
use App\Filament\Project\Resources\TravelExpenses\Tables\TravelExpensesTable;
use App\Models\TravelExpense;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TravelExpenseResource extends Resource
{
    protected static ?string $model = TravelExpense::class;

    public static function getNavigationLabel(): string
    {
        return __('navigation.travel');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.travel_expense');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.travel_expenses');
    }

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

<?php

namespace App\Filament\Project\Resources\TravelExpenses;

use App\Filament\Project\Resources\TravelExpenses\Pages\CreateTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\EditTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\ListTravelExpenses;
use App\Filament\Project\Resources\TravelExpenses\Schemas\TravelExpenseForm;
use App\Filament\Project\Resources\TravelExpenses\Tables\TravelExpensesTable;
use App\Models\Project;
use App\Models\TravelExpense;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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

    public static function canViewAny(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public static function canCreate(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public static function canEdit(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    public static function canDelete(Model $record): bool
    {
        return self::userCan(ProjectAccess::ABILITY_MANAGE_FINANCE);
    }

    private static function userCan(string $ability): bool
    {
        $project = Filament::getTenant();
        $user = auth()->user();

        if (! $project instanceof Project || $user === null) {
            return false;
        }

        return $user->can($ability, $project);
    }
}

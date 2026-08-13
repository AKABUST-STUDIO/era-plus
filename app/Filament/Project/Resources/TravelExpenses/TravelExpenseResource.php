<?php

namespace App\Filament\Project\Resources\TravelExpenses;

use App\Enums\Permissions\TravelExpensePermission;
use App\Filament\Contracts\HasProjectPermissions;
use App\Filament\Project\Resources\TravelExpenses\Pages\ListTravelExpenses;
use App\Filament\Project\Resources\TravelExpenses\Schemas\TravelExpenseForm;
use App\Filament\Project\Resources\TravelExpenses\Tables\TravelExpensesTable;
use App\Models\Project;
use App\Models\Project\TravelExpense;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TravelExpenseResource extends Resource implements HasProjectPermissions
{
    protected static ?string $model = TravelExpense::class;

    protected static ?string $slug = 'finance';

    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('navigation.finance');
    }

    public static function getModelLabel(): string
    {
        return __('finance.expense');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return (string) $record?->projectParticipant?->participable?->name;
    }

    public static function form(Schema $schema): Schema
    {
        return TravelExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelExpensesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTravelExpenses::route('/'),
        ];
    }

    public static function canManageCountryLimits(): bool
    {
        return self::userCan(TravelExpensePermission::CountryLimits);
    }

    public static function canViewAllExpenses(): bool
    {
        return self::userCan(TravelExpensePermission::ViewAny);
    }

    public static function canImport(): bool
    {
        return self::userCan(TravelExpensePermission::Import);
    }

    public static function canExport(): bool
    {
        return self::userCan(TravelExpensePermission::Export);
    }

    private static function userCan(TravelExpensePermission $permission): bool
    {
        $project = Filament::getTenant();
        $user = auth()->user();

        if (! $project instanceof Project || $user === null) {
            return false;
        }

        return app(ProjectAccess::class)->can($user, $permission->value, $project);
    }
}

<?php

namespace App\Filament\Project\Resources\FinanceEntries;

use App\Filament\Project\Resources\FinanceEntries\Pages\CreateFinanceEntry;
use App\Filament\Project\Resources\FinanceEntries\Pages\EditFinanceEntry;
use App\Filament\Project\Resources\FinanceEntries\Pages\ListFinanceEntries;
use App\Filament\Project\Resources\FinanceEntries\Schemas\FinanceEntryForm;
use App\Filament\Project\Resources\FinanceEntries\Tables\FinanceEntriesTable;
use App\Models\FinanceEntry;
use App\Models\Project;
use App\Services\ProjectAccess;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FinanceEntryResource extends Resource
{
    protected static ?string $model = FinanceEntry::class;

    protected static ?string $recordTitleAttribute = 'description';

    public static function getNavigationLabel(): string
    {
        return __('navigation.finance');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.finance_entry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.finance_entries');
    }

    public static function canViewAny(): bool
    {
        return self::userCan(ProjectAccess::ABILITY_VIEW_FINANCE);
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

    public static function form(Schema $schema): Schema
    {
        return FinanceEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinanceEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinanceEntries::route('/'),
            'create' => CreateFinanceEntry::route('/create'),
            'edit' => EditFinanceEntry::route('/{record}/edit'),
        ];
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

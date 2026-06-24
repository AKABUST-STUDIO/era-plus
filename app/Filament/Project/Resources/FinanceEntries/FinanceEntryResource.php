<?php

namespace App\Filament\Project\Resources\FinanceEntries;

use App\Filament\Project\Resources\FinanceEntries\Pages\CreateFinanceEntry;
use App\Filament\Project\Resources\FinanceEntries\Pages\EditFinanceEntry;
use App\Filament\Project\Resources\FinanceEntries\Pages\ListFinanceEntries;
use App\Filament\Project\Resources\FinanceEntries\Schemas\FinanceEntryForm;
use App\Filament\Project\Resources\FinanceEntries\Tables\FinanceEntriesTable;
use App\Models\FinanceEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinanceEntryResource extends Resource
{
    protected static ?string $model = FinanceEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $navigationLabel = 'Finance';

    protected static ?string $modelLabel = 'finance entry';

    protected static ?string $pluralModelLabel = 'finance entries';

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
}

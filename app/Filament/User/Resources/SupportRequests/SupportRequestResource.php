<?php

namespace App\Filament\User\Resources\SupportRequests;

use App\Filament\User\Resources\SupportRequests\Pages\ListSupportRequests;
use App\Filament\User\Resources\SupportRequests\Schemas\SupportRequestForm;
use App\Filament\User\Resources\SupportRequests\Tables\SupportRequestsTable;
use App\Models\SupportRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class SupportRequestResource extends Resource
{
    protected static ?string $model = SupportRequest::class;

    protected static ?int $navigationSort = 30;

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationLabel(): string
    {
        return __('user.support.title');
    }

    public static function getModelLabel(): string
    {
        return __('user.support.model');
    }

    public static function form(Schema $schema): Schema
    {
        return SupportRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportRequests::route('/'),
        ];
    }
}

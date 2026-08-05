<?php

namespace App\Filament\User\Resources\SupportTickets;

use App\Filament\User\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\User\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\User\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

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
        return SupportTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
        ];
    }
}

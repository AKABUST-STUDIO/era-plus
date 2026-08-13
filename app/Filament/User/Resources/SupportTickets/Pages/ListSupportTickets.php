<?php

namespace App\Filament\User\Resources\SupportTickets\Pages;

use App\Filament\User\Resources\SupportTickets\Actions\CreateSupportTicketAction;
use App\Filament\User\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateSupportTicketAction::make(),
        ];
    }
}

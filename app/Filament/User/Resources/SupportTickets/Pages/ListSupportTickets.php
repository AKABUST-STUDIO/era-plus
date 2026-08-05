<?php

namespace App\Filament\User\Resources\SupportTickets\Pages;

use App\Filament\User\Resources\SupportTickets\Actions\CreateSupportTicketAction;
use App\Filament\User\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateSupportTicketAction::make(),
        ];
    }
}

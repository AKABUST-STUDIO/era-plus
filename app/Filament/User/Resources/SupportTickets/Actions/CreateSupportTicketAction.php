<?php

namespace App\Filament\User\Resources\SupportTickets\Actions;

use App\Enums\SupportTicket\SupportTicketStatus;
use App\Filament\User\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Models\SupportTicket;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class CreateSupportTicketAction
{
    public static function make(string $name = 'create'): CreateAction
    {
        return CreateAction::make($name)
            ->model(SupportTicket::class)
            ->label(__('user.support.actions.new'))
            ->icon('lucide-life-buoy')
            ->modalIcon('lucide-life-buoy')
            ->modalHeading(__('user.support.new.heading'))
            ->modalDescription(__('user.support.new.description'))
            ->modalWidth(Width::Large)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalSubmitActionLabel(__('user.support.submit'))
            ->createAnother(false)
            ->schema(SupportTicketForm::fields())
            ->mutateDataUsing(fn (array $data): array => [
                ...$data,
                'user_id' => auth()->id(),
                'status' => SupportTicketStatus::Open,
                'priority' => 'normal',
            ])
            ->successNotificationTitle(__('notifications.support_submitted'));
    }
}

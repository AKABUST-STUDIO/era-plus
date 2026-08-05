<?php

namespace App\Filament\User\Resources\SupportTickets\Actions;

use App\Enums\SupportTicket\SupportTicketStatus;
use App\Filament\User\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class CreateSupportTicketAction
{
    public static function make(string $name = 'create'): Action
    {
        return Action::make($name)
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
            ->schema(SupportTicketForm::fields())
            ->action(function (array $data): void {
                SupportTicket::create([
                    'user_id' => auth()->id(),
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'status' => SupportTicketStatus::Open,
                    'priority' => 'normal',
                ]);

                Notification::make()
                    ->title(__('notifications.support_submitted'))
                    ->success()
                    ->send();
            });
    }
}

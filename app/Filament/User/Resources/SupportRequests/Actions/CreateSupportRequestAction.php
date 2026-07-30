<?php

namespace App\Filament\User\Resources\SupportRequests\Actions;

use App\Enums\SupportRequest\SupportRequestStatus;
use App\Filament\User\Resources\SupportRequests\Schemas\SupportRequestForm;
use App\Filament\User\Resources\SupportRequests\SupportRequestResource;
use App\Models\SupportRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class CreateSupportRequestAction
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
            ->schema(SupportRequestForm::fields())
            ->action(function (array $data): void {
                SupportRequest::create([
                    'user_id' => auth()->id(),
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'status' => SupportRequestStatus::Open,
                    'priority' => 'normal',
                ]);

                Notification::make()
                    ->title(__('notifications.support_submitted'))
                    ->success()
                    ->send();
            });
    }
}

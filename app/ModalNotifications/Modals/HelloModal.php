<?php

namespace App\ModalNotifications\Modals;

use App\ModalNotifications\Contracts\ModalDefinition;
use App\ModalNotifications\ModalPayload;
use App\ModalNotifications\Payloads\HelloModalPayload;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

/**
 * @extends ModalDefinition<HelloModalPayload>
 */
class HelloModal extends ModalDefinition
{
    public static function payloadClass(): string
    {
        return HelloModalPayload::class;
    }

    public static function modal(ModalPayload $payload): Action
    {
        return Action::make('modalNotification')
            ->modalHeading('Hello '.$payload->name.' 👋')
            ->modalDescription('This is a test modal notification rendered by the modal dispatcher.')
            ->modalWidth(Width::Large)
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->schema([
                Text::make($payload->body ?? 'If you can read this, the modal notification system is wired up correctly.'),
            ])
            ->modalSubmitActionLabel('Got it')
            ->action(function (): void {
                Notification::make()
                    ->title('Test acknowledged')
                    ->success()
                    ->send();
            });
    }
}

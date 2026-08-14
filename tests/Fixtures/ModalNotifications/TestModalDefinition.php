<?php

namespace Tests\Fixtures\ModalNotifications;

use App\ModalNotifications\Contracts\ModalDefinition;
use App\ModalNotifications\ModalPayload;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

/**
 * @extends ModalDefinition<TestModalPayload>
 */
class TestModalDefinition extends ModalDefinition
{
    public static function payloadClass(): string
    {
        return TestModalPayload::class;
    }

    public static function modal(ModalPayload $payload): Action
    {
        return Action::make('modalNotification')
            ->modalHeading('Test: '.$payload->note)
            ->modalWidth(Width::tryFrom($payload->width) ?? Width::Large)
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->schema([TextInput::make('note')->default($payload->note)])
            ->modalSubmitActionLabel('Confirm')
            ->modalCancelAction($payload->dismissible ? null : false);
    }
}

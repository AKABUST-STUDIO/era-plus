<?php

namespace App\ModalNotifications\Contracts;

use App\ModalNotifications\ModalPayload;
use Filament\Actions\Action;

/**
 * @template TPayload of ModalPayload
 */
abstract class ModalDefinition
{
    /**
     * @return class-string<TPayload>
     */
    abstract public static function payloadClass(): string;

    /**
     * @param  TPayload  $payload
     */
    abstract public static function modal(ModalPayload $payload): Action;
}

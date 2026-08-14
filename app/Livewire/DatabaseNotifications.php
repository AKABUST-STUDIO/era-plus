<?php

namespace App\Livewire;

use App\ModalNotifications\Contracts\ModalDefinition;
use App\ModalNotifications\ModalPayload;
use App\ModalNotifications\Notifications\ModalDatabaseNotification;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Illuminate\Notifications\DatabaseNotification;
use Throwable;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;

    public ?string $currentNotificationId = null;

    /** @var class-string<ModalDefinition>|null */
    public ?string $currentModalClass = null;

    /** @var array<string, mixed> */
    public array $currentPayloadData = [];

    public function loadNextModal(): void
    {
        if ($this->currentNotificationId !== null) {
            return;
        }

        $user = $this->getUser();

        if (! $user instanceof User) {
            return;
        }

        $query = $user->unreadNotifications()
            ->where('type', ModalDatabaseNotification::class)
            ->reorder('created_at')
            ->orderBy('id');

        foreach ($query->cursor() as $notification) {
            /** @var DatabaseNotification $notification */
            $data = $notification->data;
            $modalClass = $data['modal_class'] ?? null;

            if (! is_string($modalClass) || ! class_exists($modalClass) || ! is_subclass_of($modalClass, ModalDefinition::class)) {
                $notification->markAsRead();

                continue;
            }

            try {
                $this->hydratePayload($modalClass, $data['payload'] ?? []);
            } catch (Throwable $e) {
                report($e);
                $notification->markAsRead();

                continue;
            }

            $this->currentNotificationId = $notification->id;
            $this->currentModalClass = $modalClass;
            $this->currentPayloadData = $data['payload'] ?? [];

            $this->mountAction('modalNotification');

            return;
        }
    }

    public function modalNotificationAction(): Action
    {
        if ($this->currentNotificationId === null || $this->currentModalClass === null) {
            return Action::make('modalNotification')->hidden();
        }

        $modalClass = $this->currentModalClass;

        if (! class_exists($modalClass) || ! is_subclass_of($modalClass, ModalDefinition::class)) {
            return Action::make('modalNotification')->hidden();
        }

        $payload = $this->hydratePayload($modalClass, $this->currentPayloadData);
        $action = $modalClass::modal($payload);

        $action->after(fn () => $this->handleAcknowledged());

        $cancel = $action->getModalCancelAction();

        if ($cancel !== null) {
            $cancel->after(fn () => $this->handleAcknowledged());
            $action->modalCancelAction($cancel);
        }

        return $action;
    }

    private function handleAcknowledged(): void
    {
        if ($this->currentNotificationId !== null) {
            DatabaseNotification::where('id', $this->currentNotificationId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $this->currentNotificationId = null;
        $this->currentModalClass = null;
        $this->currentPayloadData = [];

        $this->loadNextModal();
    }

    /**
     * @param  class-string<ModalDefinition>  $modalClass
     * @param  array<string, mixed>  $data
     */
    private function hydratePayload(string $modalClass, array $data): ModalPayload
    {
        $payloadClass = $modalClass::payloadClass();

        /** @var ModalPayload $payload */
        $payload = $payloadClass::from($data);

        return $payload;
    }
}

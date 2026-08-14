<?php

namespace App\ModalNotifications\Notifications;

use App\ModalNotifications\ModalPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModalDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $modalClass,
        public ModalPayload $payload,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'modal_class' => $this->modalClass,
            'payload' => $this->payload->toArray(),
        ];
    }
}

<?php

namespace App\ModalNotifications\Payloads;

use App\ModalNotifications\ModalPayload;

class HelloModalPayload extends ModalPayload
{
    public function __construct(
        public string $name,
        public ?string $body = null,
    ) {}
}

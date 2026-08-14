<?php

namespace Tests\Fixtures\ModalNotifications;

use App\ModalNotifications\ModalPayload;

class TestModalPayload extends ModalPayload
{
    public function __construct(
        public string $note = 'test',
        public bool $dismissible = true,
        public string $width = 'lg',
    ) {}
}

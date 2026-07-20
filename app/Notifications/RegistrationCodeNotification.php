<?php

namespace App\Notifications;

class RegistrationCodeNotification extends AuthenticationCodeNotification
{
    public function subject(): string
    {
        return __('emails.registration_code.subject', [
            'app' => str(config('app.name'))->ucfirst(),
            'code' => $this->oneTimePassword->password,
        ]);
    }

    protected function markdownView(): string
    {
        return 'emails.registration-code';
    }
}

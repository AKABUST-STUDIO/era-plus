<?php

namespace App\Notifications;

class LoginCodeNotification extends AuthenticationCodeNotification
{
    public function subject(): string
    {
        return __('emails.login_code.subject', [
            'code' => $this->oneTimePassword->password,
        ]);
    }

    protected function markdownView(): string
    {
        return 'emails.login-code';
    }
}

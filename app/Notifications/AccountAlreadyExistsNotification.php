<?php

namespace App\Notifications;

class AccountAlreadyExistsNotification extends AuthenticationCodeNotification
{
    public function subject(): string
    {
        return __('emails.account_already_exists.subject', [
            'app' => str(config('app.name'))->ucfirst(),
        ]);
    }

    protected function markdownView(): string
    {
        return 'emails.account-already-exists';
    }
}

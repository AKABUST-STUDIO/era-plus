<?php

namespace App\Observers;

use App\Mail\AccountCreated;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    public function created(User $user)
    {
        //
    }

    public function deleting(User $user)
    {
        $user->clearMediaCollection();

        if ($user->stripe_id !== null) {
            $user->asStripeCustomer()->delete([]);
        }
    }
}

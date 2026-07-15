<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function deleting(User $user)
    {
        $user->clearMediaCollection();

        if ($user->stripe_id !== null) {
            $user->asStripeCustomer()->delete([]);
        }
    }
}

<?php

namespace App\Observers;

use App\Mail\SupportRequestReceived;
use App\Models\SupportRequest;
use Illuminate\Support\Facades\Mail;

class SupportRequestObserver
{
    public function created(SupportRequest $supportRequest): void
    {
        Mail::to(config('app.support.recipient'))->queue(new SupportRequestReceived($supportRequest));
    }
}

<?php

namespace App\Observers;

use App\Mail\SupportTicketReceived;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Mail;

class SupportTicketObserver
{
    public function created(SupportTicket $supportTicket): void
    {
        Mail::to(config('app.support.recipient'))->queue(new SupportTicketReceived($supportTicket));
    }
}

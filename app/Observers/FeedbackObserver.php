<?php

namespace App\Observers;

use App\Mail\FeedbackReceived;
use App\Models\Feedback;
use Illuminate\Support\Facades\Mail;

class FeedbackObserver
{
    public function created(Feedback $feedback): void
    {
        Mail::to(config('app.feedback.recipient'))->queue(new FeedbackReceived($feedback));
    }
}

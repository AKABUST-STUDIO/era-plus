<?php

use App\Mail\FeedbackReceived;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('observer emails configured recipient on create', function (): void {
    Mail::fake();

    config()->set('app.feedback.recipient', 'feedback@era-plus.network');

    Feedback::create([
        'user_id' => $this->user->id,
        'subject' => 'Love the new sidebar',
        'rating' => 5,
        'description' => 'It looks great.',
    ]);

    Mail::assertQueued(FeedbackReceived::class, function (FeedbackReceived $mail): bool {
        return $mail->hasTo('feedback@era-plus.network')
            && $mail->feedback->subject === 'Love the new sidebar'
            && $mail->feedback->rating === 5;
    });
});

test('observer only fires on create', function (): void {
    Mail::fake();

    $feedback = Feedback::create([
        'user_id' => $this->user->id,
        'subject' => 'Hi',
        'rating' => 3,
        'description' => 'Body',
    ]);

    Mail::assertQueuedCount(1);

    $feedback->update(['rating' => 4]);

    Mail::assertQueuedCount(1);
});

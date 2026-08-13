<?php

namespace Tests\Feature;

use App\Mail\FeedbackReceived;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_observer_emails_configured_recipient_on_create(): void
    {
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
    }

    public function test_observer_only_fires_on_create(): void
    {
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
    }
}

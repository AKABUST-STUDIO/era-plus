<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Listeners\HandleUserUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserUpdatedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_a_user_dispatches_the_event_with_the_user(): void
    {
        $user = User::factory()->create();

        Event::fake([UserUpdated::class]);

        $user->update(['name' => 'Renamed']);

        Event::assertDispatched(
            UserUpdated::class,
            fn (UserUpdated $event): bool => $event->user->is($user),
        );
    }

    public function test_creating_a_user_does_not_dispatch_the_event(): void
    {
        Event::fake([UserUpdated::class]);

        User::factory()->create();

        Event::assertNotDispatched(UserUpdated::class);
    }

    public function test_saving_a_user_without_changes_does_not_dispatch_the_event(): void
    {
        $user = User::factory()->create();

        Event::fake([UserUpdated::class]);

        $user->save();

        Event::assertNotDispatched(UserUpdated::class);
    }

    public function test_the_listener_is_attached_to_the_event(): void
    {
        Event::fake();

        Event::assertListening(UserUpdated::class, HandleUserUpdated::class);
    }
}

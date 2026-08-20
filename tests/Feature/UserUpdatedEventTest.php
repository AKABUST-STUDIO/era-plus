<?php

use App\Events\UserUpdated;
use App\Listeners\HandleUserUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('updating a user dispatches the event with the user', function (): void {
    $user = User::factory()->create();

    Event::fake([UserUpdated::class]);

    $user->update(['name' => 'Renamed']);

    Event::assertDispatched(
        UserUpdated::class,
        fn (UserUpdated $event): bool => $event->user->is($user),
    );
});

test('creating a user does not dispatch the event', function (): void {
    Event::fake([UserUpdated::class]);

    User::factory()->create();

    Event::assertNotDispatched(UserUpdated::class);
});

test('saving a user without changes does not dispatch the event', function (): void {
    $user = User::factory()->create();

    Event::fake([UserUpdated::class]);

    $user->save();

    Event::assertNotDispatched(UserUpdated::class);
});

test('the listener is attached to the event', function (): void {
    Event::fake();

    Event::assertListening(UserUpdated::class, HandleUserUpdated::class);
});

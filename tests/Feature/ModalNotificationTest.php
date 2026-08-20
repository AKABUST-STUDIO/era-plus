<?php

use App\Livewire\DatabaseNotifications;
use App\ModalNotifications\ModalPayload;
use App\ModalNotifications\Notifications\ModalDatabaseNotification;
use App\Models\User;
use Filament\Support\Enums\Width;
use Livewire\Livewire;
use Tests\Fixtures\ModalNotifications\TestModalDefinition;
use Tests\Fixtures\ModalNotifications\TestModalPayload;

test('dispatch writes notification with correct shape', function (): void {
    $user = User::factory()->create();

    $user->notifyWithModal(TestModalDefinition::class, new TestModalPayload(
        note: 'hello',
        dismissible: false,
        width: 'xl',
    ));

    $notification = $user->notifications()->latest('created_at')->firstOrFail();

    $this->assertSame(ModalDatabaseNotification::class, $notification->type);
    $this->assertSame($user->id, $notification->notifiable_id);
    $this->assertSame(User::class, $notification->notifiable_type);
    $this->assertNull($notification->read_at);

    $data = $notification->data;
    $this->assertSame(TestModalDefinition::class, $data['modal_class']);
    $this->assertSame([
        'note' => 'hello',
        'dismissible' => false,
        'width' => 'xl',
    ], $data['payload']);
});

test('dispatch rejects mismatched payload', function (): void {
    $user = User::factory()->create();

    $this->expectException(InvalidArgumentException::class);

    $user->notifyWithModal(TestModalDefinition::class, new class extends ModalPayload
    {
        public string $foo = 'bar';
    });
});

test('dispatcher only surfaces target users modals', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->notifyWithModal(TestModalDefinition::class, new TestModalPayload(note: 'A'));
    $userB->notifyWithModal(TestModalDefinition::class, new TestModalPayload(note: 'B'));

    $modalA = $userA->notifications()->latest('created_at')->firstOrFail();

    $this->actingAs($userA);

    Livewire::test(DatabaseNotifications::class)
        ->call('loadNextModal')
        ->assertSet('currentNotificationId', $modalA->id)
        ->assertSet('currentPayloadData.note', 'A');
});

test('submit marks notification read and advances to next', function (): void {
    $user = User::factory()->create();

    $user->notifyWithModal(TestModalDefinition::class, new TestModalPayload(note: 'first'));
    $first = $user->notifications()->latest('created_at')->firstOrFail();

    $this->travel(1)->second();

    $user->notifyWithModal(TestModalDefinition::class, new TestModalPayload(note: 'second'));
    $second = $user->notifications()->latest('created_at')->firstOrFail();

    $this->actingAs($user);

    Livewire::test(DatabaseNotifications::class)
        ->call('loadNextModal')
        ->assertSet('currentNotificationId', $first->id)
        ->callMountedAction()
        ->assertSet('currentNotificationId', $second->id);

    $this->assertNotNull($first->refresh()->read_at);
    $this->assertNull($second->refresh()->read_at);
});

test('non dismissible modal hides cancel and close paths', function (): void {
    $user = User::factory()->create();
    $user->notifyWithModal(TestModalDefinition::class, new TestModalPayload(dismissible: false));

    $this->actingAs($user);

    /** @var DatabaseNotifications $component */
    $component = Livewire::test(DatabaseNotifications::class)->call('loadNextModal')->instance();
    $action = $component->modalNotificationAction();

    $this->assertFalse($action->hasModalCloseButton());
    $this->assertNull($action->getModalCancelAction());
    $this->assertFalse($action->isModalClosedByClickingAway());
    $this->assertFalse($action->isModalClosedByEscaping());
});

test('custom width from modal is applied', function (): void {
    $user = User::factory()->create();
    $user->notifyWithModal(TestModalDefinition::class, new TestModalPayload(width: '2xl'));

    $this->actingAs($user);

    /** @var DatabaseNotifications $component */
    $component = Livewire::test(DatabaseNotifications::class)->call('loadNextModal')->instance();

    $this->assertSame(Width::TwoExtraLarge, $component->modalNotificationAction()->getModalWidth());
});

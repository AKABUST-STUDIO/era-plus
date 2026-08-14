<?php

namespace Tests\Feature;

use App\Livewire\DatabaseNotifications;
use App\ModalNotifications\ModalPayload;
use App\ModalNotifications\Notifications\ModalDatabaseNotification;
use App\Models\User;
use Filament\Support\Enums\Width;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\Fixtures\ModalNotifications\TestModalDefinition;
use Tests\Fixtures\ModalNotifications\TestModalPayload;
use Tests\TestCase;

class ModalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_writes_notification_with_correct_shape(): void
    {
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
    }

    public function test_dispatch_rejects_mismatched_payload(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $user->notifyWithModal(TestModalDefinition::class, new class extends ModalPayload
        {
            public string $foo = 'bar';
        });
    }

    public function test_dispatcher_only_surfaces_target_users_modals(): void
    {
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
    }

    public function test_submit_marks_notification_read_and_advances_to_next(): void
    {
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
    }

    public function test_non_dismissible_modal_hides_cancel_and_close_paths(): void
    {
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
    }

    public function test_custom_width_from_modal_is_applied(): void
    {
        $user = User::factory()->create();
        $user->notifyWithModal(TestModalDefinition::class, new TestModalPayload(width: '2xl'));

        $this->actingAs($user);

        /** @var DatabaseNotifications $component */
        $component = Livewire::test(DatabaseNotifications::class)->call('loadNextModal')->instance();

        $this->assertSame(Width::TwoExtraLarge, $component->modalNotificationAction()->getModalWidth());
    }
}

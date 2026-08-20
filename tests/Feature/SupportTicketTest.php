<?php

use App\Enums\SupportTicket\SupportTicketStatus;
use App\Filament\User\Resources\SupportTickets\Actions\CreateSupportTicketAction;
use App\Filament\User\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Mail\SupportTicketReceived;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('user'));
});

test('list page renders', function (): void {
    Livewire::test(ListSupportTickets::class)->assertSuccessful();
});

test('user can create ticket from list header action', function (): void {
    Mail::fake();
    config()->set('app.support.recipient', 'support@era-plus.network');

    Livewire::test(ListSupportTickets::class)
        ->callAction(CreateSupportTicketAction::make()->getName(), data: [
            'subject' => 'Cannot upload PDF',
            'body' => 'I get a 500 when uploading.',
        ]);

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $this->user->id,
        'subject' => 'Cannot upload PDF',
        'status' => SupportTicketStatus::Open->value,
    ]);

    Mail::assertQueued(SupportTicketReceived::class, function (SupportTicketReceived $mail): bool {
        return $mail->hasTo('support@era-plus.network');
    });
});

test('subject and body are required', function (): void {
    Livewire::test(ListSupportTickets::class)
        ->callAction(CreateSupportTicketAction::make()->getName(), data: [
            'subject' => null,
            'body' => null,
        ])
        ->assertHasActionErrors(['subject', 'body']);
});

test('list only shows current user requests', function (): void {
    $mine = SupportTicket::factory()->create(['user_id' => $this->user->id]);

    $stranger = SupportTicket::factory()->create();

    Livewire::test(ListSupportTickets::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$stranger]);
});

test('sort filter defaults to opened and offers every sortable column', function (): void {
    $sort = null;

    foreach (Livewire::test(ListSupportTickets::class)->instance()->getTable()->getFilter('sort')->getSchema()->getFlatComponents(withHidden: true) as $child) {
        if ($child->getName() === 'sort') {
            $sort = $child;
        }
    }

    $this->assertSame('created_at', $sort?->getDefaultState());
    $this->assertSame(['created_at', 'status', 'subject'], array_keys($sort->getOptions()));
});

test('requests can be sorted by subject', function (): void {
    $last = SupportTicket::factory()->create(['user_id' => $this->user->id, 'subject' => 'Zebra crossing']);
    $first = SupportTicket::factory()->create(['user_id' => $this->user->id, 'subject' => 'Ailing upload']);

    Livewire::test(ListSupportTickets::class)
        ->sortTable('subject')
        ->assertCanSeeTableRecords([$first, $last], inOrder: true);
});

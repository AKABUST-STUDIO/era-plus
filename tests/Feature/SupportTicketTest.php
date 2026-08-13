<?php

namespace Tests\Feature;

use App\Enums\SupportTicket\SupportTicketStatus;
use App\Filament\User\Resources\SupportTickets\Actions\CreateSupportTicketAction;
use App\Filament\User\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Mail\SupportTicketReceived;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    public function test_list_page_renders(): void
    {
        Livewire::test(ListSupportTickets::class)->assertSuccessful();
    }

    public function test_user_can_create_ticket_from_list_header_action(): void
    {
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
    }

    public function test_subject_and_body_are_required(): void
    {
        Livewire::test(ListSupportTickets::class)
            ->callAction(CreateSupportTicketAction::make()->getName(), data: [
                'subject' => null,
                'body' => null,
            ])
            ->assertHasActionErrors(['subject', 'body']);
    }

    public function test_list_only_shows_current_user_requests(): void
    {
        $mine = SupportTicket::factory()->create(['user_id' => $this->user->id]);

        $stranger = SupportTicket::factory()->create();

        Livewire::test(ListSupportTickets::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$stranger]);
    }

    public function test_sort_filter_defaults_to_opened_and_offers_every_sortable_column(): void
    {
        $sort = null;

        foreach (Livewire::test(ListSupportTickets::class)->instance()->getTable()->getFilter('sort')->getSchema()->getFlatComponents(withHidden: true) as $child) {
            if ($child->getName() === 'sort') {
                $sort = $child;
            }
        }

        $this->assertSame('created_at', $sort?->getDefaultState());
        $this->assertSame(['created_at', 'status', 'subject'], array_keys($sort->getOptions()));
    }

    public function test_requests_can_be_sorted_by_subject(): void
    {
        $last = SupportTicket::factory()->create(['user_id' => $this->user->id, 'subject' => 'Zebra crossing']);
        $first = SupportTicket::factory()->create(['user_id' => $this->user->id, 'subject' => 'Ailing upload']);

        Livewire::test(ListSupportTickets::class)
            ->sortTable('subject')
            ->assertCanSeeTableRecords([$first, $last], inOrder: true);
    }
}

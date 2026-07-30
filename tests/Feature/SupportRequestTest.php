<?php

namespace Tests\Feature;

use App\Enums\SupportRequest\SupportRequestStatus;
use App\Filament\User\Resources\SupportRequests\Actions\CreateSupportRequestAction;
use App\Filament\User\Resources\SupportRequests\Pages\ListSupportRequests;
use App\Mail\SupportRequestReceived;
use App\Models\SupportRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class SupportRequestTest extends TestCase
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
        Livewire::test(ListSupportRequests::class)->assertSuccessful();
    }

    public function test_user_can_create_ticket_from_list_header_action(): void
    {
        Mail::fake();
        config()->set('app.support.recipient', 'support@rasmo.eu');

        Livewire::test(ListSupportRequests::class)
            ->callAction(CreateSupportRequestAction::make()->getName(), data: [
                'subject' => 'Cannot upload PDF',
                'body' => 'I get a 500 when uploading.',
            ]);

        $this->assertDatabaseHas('support_requests', [
            'user_id' => $this->user->id,
            'subject' => 'Cannot upload PDF',
            'status' => SupportRequestStatus::Open->value,
        ]);

        Mail::assertQueued(SupportRequestReceived::class, function (SupportRequestReceived $mail): bool {
            return $mail->hasTo('support@rasmo.eu');
        });
    }

    public function test_subject_and_body_are_required(): void
    {
        Livewire::test(ListSupportRequests::class)
            ->callAction(CreateSupportRequestAction::make()->getName(), data: [
                'subject' => null,
                'body' => null,
            ])
            ->assertHasActionErrors(['subject', 'body']);
    }

    public function test_list_only_shows_current_user_requests(): void
    {
        $mine = SupportRequest::factory()->create(['user_id' => $this->user->id]);

        $stranger = SupportRequest::factory()->create();

        Livewire::test(ListSupportRequests::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$stranger]);
    }
}

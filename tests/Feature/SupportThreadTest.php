<?php

namespace Tests\Feature;

use App\Enums\SupportRequestStatus;
use App\Filament\User\Pages\Support;
use App\Models\SupportRequest;
use App\Models\SupportRequestMessage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportThreadTest extends TestCase
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

    public function test_submitting_a_request_seeds_first_message_in_thread(): void
    {
        Livewire::test(Support::class)
            ->fillForm([
                'subject' => 'Cannot login',
                'priority' => 'high',
                'body' => 'I get a 500.',
            ], 'createForm')
            ->call('submit');

        $request = SupportRequest::query()->where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame(1, $request->messages()->count());
        $this->assertSame('I get a 500.', $request->messages()->first()->body);
    }

    public function test_reply_appends_message_and_reopens_resolved_request(): void
    {
        $request = SupportRequest::create([
            'user_id' => $this->user->id,
            'subject' => 'Issue',
            'body' => 'Body',
            'priority' => 'normal',
            'status' => SupportRequestStatus::Resolved,
            'resolved_at' => now(),
        ]);

        Livewire::test(Support::class)
            ->instance()
            ->reply($request, 'Actually still broken.');

        $fresh = $request->fresh();
        $this->assertSame(SupportRequestStatus::Open, $fresh->status);
        $this->assertNull($fresh->resolved_at);
        $this->assertSame(1, $fresh->messages()->count());
    }

    public function test_user_cannot_reply_to_other_users_request(): void
    {
        $stranger = User::factory()->create();
        $request = SupportRequest::create([
            'user_id' => $stranger->id,
            'subject' => 'Not yours',
            'body' => 'x',
            'priority' => 'normal',
            'status' => SupportRequestStatus::Open->value,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::test(Support::class)
            ->instance()
            ->reply($request, 'sneaky');
    }

    public function test_mark_resolved_flips_status_and_timestamp(): void
    {
        $request = SupportRequest::create([
            'user_id' => $this->user->id,
            'subject' => 's',
            'body' => 'b',
            'priority' => 'normal',
            'status' => SupportRequestStatus::Open->value,
        ]);

        Livewire::test(Support::class)
            ->instance()
            ->markResolved($request);

        $fresh = $request->fresh();
        $this->assertSame(SupportRequestStatus::Resolved, $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_has_unread_staff_reply_detects_staff_messages(): void
    {
        $request = SupportRequest::create([
            'user_id' => $this->user->id,
            'subject' => 's',
            'body' => 'b',
            'priority' => 'normal',
            'status' => SupportRequestStatus::Open->value,
        ]);

        $this->assertFalse($request->hasUnreadStaffReply());

        SupportRequestMessage::create([
            'support_request_id' => $request->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Staff here',
            'is_staff_reply' => true,
        ]);

        $this->assertTrue($request->fresh()->hasUnreadStaffReply());
    }

    public function test_mark_staff_replies_read_clears_unread_state(): void
    {
        $request = SupportRequest::create([
            'user_id' => $this->user->id,
            'subject' => 's',
            'body' => 'b',
            'priority' => 'normal',
            'status' => SupportRequestStatus::Open->value,
        ]);

        SupportRequestMessage::create([
            'support_request_id' => $request->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Staff here',
            'is_staff_reply' => true,
        ]);

        Livewire::test(Support::class)
            ->instance()
            ->markStaffRepliesRead($request);

        $this->assertFalse($request->fresh()->hasUnreadStaffReply());
    }

    public function test_navigation_badge_shows_count_of_unread_staff_replies(): void
    {
        $request = SupportRequest::create([
            'user_id' => $this->user->id,
            'subject' => 's',
            'body' => 'b',
            'priority' => 'normal',
            'status' => SupportRequestStatus::Open->value,
        ]);

        SupportRequestMessage::create([
            'support_request_id' => $request->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'staff 1',
            'is_staff_reply' => true,
        ]);
        SupportRequestMessage::create([
            'support_request_id' => $request->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'staff 2',
            'is_staff_reply' => true,
        ]);

        $this->assertSame('2', Support::getNavigationBadge());
    }
}

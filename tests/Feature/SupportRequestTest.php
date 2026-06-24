<?php

namespace Tests\Feature;

use App\Enums\SupportRequestStatus;
use App\Filament\User\Pages\UserSupport;
use App\Models\SupportRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_support_page_renders(): void
    {
        Livewire::test(UserSupport::class)->assertSuccessful();
    }

    public function test_user_can_submit_support_request(): void
    {
        Livewire::test(UserSupport::class)
            ->fillForm([
                'subject' => 'Cannot upload PDF',
                'priority' => 'high',
                'body' => 'I get a 500 when uploading.',
            ], 'createForm')
            ->call('submit');

        $this->assertDatabaseHas('support_requests', [
            'user_id' => $this->user->id,
            'subject' => 'Cannot upload PDF',
            'priority' => 'high',
            'status' => SupportRequestStatus::Open->value,
        ]);
    }

    public function test_subject_and_body_are_required(): void
    {
        Livewire::test(UserSupport::class)
            ->fillForm(['subject' => null, 'body' => null], 'createForm')
            ->call('submit')
            ->assertHasFormErrors(['subject', 'body'], 'createForm');
    }

    public function test_list_only_shows_current_user_requests(): void
    {
        SupportRequest::create([
            'user_id' => $this->user->id,
            'subject' => 'Mine',
            'body' => 'x',
        ]);

        $stranger = User::factory()->create();
        SupportRequest::create([
            'user_id' => $stranger->id,
            'subject' => 'Stranger',
            'body' => 'y',
        ]);

        Livewire::test(UserSupport::class)
            ->assertCanSeeTableRecords(
                SupportRequest::query()->where('user_id', $this->user->id)->get()
            )
            ->assertCanNotSeeTableRecords(
                SupportRequest::query()->where('user_id', $stranger->id)->get()
            );
    }
}

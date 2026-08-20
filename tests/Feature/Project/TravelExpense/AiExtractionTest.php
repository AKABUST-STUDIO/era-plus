<?php

namespace Tests\Feature\Project\TravelExpense;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Enums\Project\TravelExpense\AiExtractionTarget;
use App\Livewire\Project\TravelExpense\AiSuggestionBanner;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\TravelExpenseAiExtraction;
use App\Models\User;
use App\Services\Ai\TravelExpenseExtractionDispatcher;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class AiExtractionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project, ProjectRole::Admin);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_dispatcher_generates_unique_session_ids(): void
    {
        $a = TravelExpenseExtractionDispatcher::newSessionId();
        $b = TravelExpenseExtractionDispatcher::newSessionId();

        $this->assertNotSame($a, $b);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $a);
    }

    public function test_banner_stays_pending_while_extraction_has_no_result(): void
    {
        $sessionId = TravelExpenseExtractionDispatcher::newSessionId();

        TravelExpenseAiExtraction::factory()->create([
            'session_id' => $sessionId,
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'target' => AiExtractionTarget::Journey,
        ]);

        $component = Livewire::test(AiSuggestionBanner::class, [
            'sessionId' => $sessionId,
            'target' => AiExtractionTarget::Journey->value,
            'fields' => ['date', 'from', 'to'],
        ]);

        $component->assertSee(__('finance.ai_suggestion.pending'));
        $this->assertSame('pending', $component->instance()->status());
        $this->assertTrue($component->instance()->shouldPoll());
    }

    public function test_banner_shows_extracted_fields_after_completion(): void
    {
        $sessionId = TravelExpenseExtractionDispatcher::newSessionId();

        TravelExpenseAiExtraction::factory()
            ->completed(['date' => '2026-05-01', 'from' => 'Madrid', 'to' => 'Berlin'])
            ->create([
                'session_id' => $sessionId,
                'user_id' => $this->user->id,
                'project_id' => $this->project->id,
                'target' => AiExtractionTarget::Journey,
            ]);

        $component = Livewire::test(AiSuggestionBanner::class, [
            'sessionId' => $sessionId,
            'target' => AiExtractionTarget::Journey->value,
            'fields' => ['date', 'from', 'to'],
        ]);

        $component->assertSee('Madrid');
        $component->assertSee('Berlin');
        $component->assertSee(__('finance.ai_suggestion.apply'));
        $this->assertFalse($component->instance()->shouldPoll());
    }

    public function test_apply_dispatches_event_and_dismisses_banner(): void
    {
        $sessionId = TravelExpenseExtractionDispatcher::newSessionId();

        TravelExpenseAiExtraction::factory()
            ->completed(['cost' => 45.9, 'currency' => 'EUR'])
            ->create([
                'session_id' => $sessionId,
                'user_id' => $this->user->id,
                'project_id' => $this->project->id,
                'target' => AiExtractionTarget::Payment,
            ]);

        Livewire::test(AiSuggestionBanner::class, [
            'sessionId' => $sessionId,
            'target' => AiExtractionTarget::Payment->value,
            'fields' => ['cost', 'currency'],
        ])
            ->call('apply')
            ->assertDispatched('ai-suggestion::apply', target: 'payment', fields: ['cost' => 45.9, 'currency' => 'EUR'])
            ->assertSet('dismissed', true);
    }

    public function test_failed_extraction_hides_the_banner(): void
    {
        $sessionId = TravelExpenseExtractionDispatcher::newSessionId();

        TravelExpenseAiExtraction::factory()
            ->failed('provider down')
            ->create([
                'session_id' => $sessionId,
                'user_id' => $this->user->id,
                'project_id' => $this->project->id,
                'target' => AiExtractionTarget::Journey,
            ]);

        $component = Livewire::test(AiSuggestionBanner::class, [
            'sessionId' => $sessionId,
            'target' => AiExtractionTarget::Journey->value,
            'fields' => ['date'],
        ]);

        $this->assertSame('failed', $component->instance()->status());
        $this->assertFalse($component->instance()->shouldPoll());
        $this->assertNull($component->instance()->extraction());
    }

    public function test_dismiss_hides_the_banner_and_stops_polling(): void
    {
        $sessionId = TravelExpenseExtractionDispatcher::newSessionId();

        TravelExpenseAiExtraction::factory()->create([
            'session_id' => $sessionId,
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'target' => AiExtractionTarget::Journey,
        ]);

        $component = Livewire::test(AiSuggestionBanner::class, [
            'sessionId' => $sessionId,
            'target' => AiExtractionTarget::Journey->value,
            'fields' => ['date'],
        ])
            ->call('dismiss')
            ->assertSet('dismissed', true);

        $this->assertFalse($component->instance()->shouldPoll());
    }

    public function test_prune_command_deletes_old_extractions_and_their_files(): void
    {
        Storage::fake('local');

        $stale = TravelExpenseAiExtraction::factory()->create([
            'session_id' => 'stale-session',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ]);
        $stale->forceFill(['created_at' => now()->subHours(48)])->save();
        Storage::disk('local')->put('ai-extractions/stale-session/journey/x.png', 'x');

        $fresh = TravelExpenseAiExtraction::factory()->create([
            'session_id' => 'fresh-session',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ]);

        $this->artisan('travel-expense-extractions:prune')->assertSuccessful();

        $this->assertNull(TravelExpenseAiExtraction::query()->find($stale->id));
        $this->assertNotNull(TravelExpenseAiExtraction::query()->find($fresh->id));
        $this->assertFalse(Storage::disk('local')->exists('ai-extractions/stale-session/journey/x.png'));
    }

    public function test_another_users_extraction_is_ignored(): void
    {
        $sessionId = TravelExpenseExtractionDispatcher::newSessionId();
        $stranger = User::factory()->create();

        TravelExpenseAiExtraction::factory()
            ->completed(['from' => 'Leaked'])
            ->create([
                'session_id' => $sessionId,
                'user_id' => $stranger->id,
                'project_id' => $this->project->id,
                'target' => AiExtractionTarget::Journey,
            ]);

        $component = Livewire::test(AiSuggestionBanner::class, [
            'sessionId' => $sessionId,
            'target' => AiExtractionTarget::Journey->value,
            'fields' => ['from'],
        ]);

        $component->assertDontSee('Leaked');
        $this->assertNull($component->instance()->extraction());
    }
}

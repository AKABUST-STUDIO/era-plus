<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Project\ProjectRole;
use App\Enums\Project\TravelType;
use App\Filament\Forms\Components\FilterGroupDropdown;
use App\Filament\Project\Resources\TravelExpenses\Pages\ListTravelExpenses;
use App\Filament\Project\Resources\TravelExpenses\Schemas\TravelExpenseForm;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Filament\Tables\Filters\EnumSelect;
use App\Livewire\Finance\Reimbursement;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\CountryLimit;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use App\Models\User;
use Database\Seeders\TravelExpenseSeeder;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceTest extends TestCase
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

    private function countryId(string $iso2 = 'ES', string $name = 'Spain'): int
    {
        $existing = DB::table('countries')->where('iso2', $iso2)->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        DB::table('countries')->insert([
            'iso2' => $iso2,
            'name' => $name,
            'status' => 1,
            'phone_code' => '0',
            'iso3' => $iso2.'X',
            'region' => 'Europe',
            'subregion' => 'Europe',
        ]);

        return (int) DB::table('countries')->where('iso2', $iso2)->value('id');
    }

    private function participation(?Project $project = null, ?int $countryId = null, string $name = 'Ana Torres'): ProjectParticipant
    {
        $project ??= $this->project;
        $participant = Participant::factory()->create(['name' => $name]);
        $sending = ParticipantOrganization::create(['name' => 'Universidad']);

        return $project->addParticipant($participant, $countryId ?? $this->countryId(), $sending);
    }

    public function test_travel_expenses_table_has_no_project_id_column(): void
    {
        $this->assertFalse(Schema::hasColumn('travel_expenses', 'project_id'));
        $this->assertTrue(Schema::hasColumn('travel_expenses', 'project_participant_id'));
    }

    public function test_expense_resolves_its_project_through_the_participation(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $this->assertSame($this->project->id, $expense->projectParticipant->project_id);
        $this->assertTrue($this->project->travelExpenses->contains($expense));
    }

    public function test_expense_links_to_the_country_limit_through_the_participant_country(): void
    {
        $countryId = $this->countryId();
        $participation = $this->participation(countryId: $countryId);
        $expense = TravelExpense::factory()->forParticipant($participation)->costing(300)->create();

        CountryLimit::create([
            'project_id' => $this->project->id,
            'country_id' => $countryId,
            'amount_eur' => 275,
        ]);

        $this->assertNotNull($expense->fresh()->countryLimit);
        $this->assertSame('275.00', $expense->fresh()->countryLimit->amount_eur);
    }

    public function test_country_limit_of_another_project_does_not_leak(): void
    {
        $countryId = $this->countryId();
        $participation = $this->participation(countryId: $countryId);
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $otherProject = Project::factory()->for($this->organization)->create();
        CountryLimit::create([
            'project_id' => $otherProject->id,
            'country_id' => $countryId,
            'amount_eur' => 999,
        ]);

        $this->assertNull($expense->fresh()->countryLimit);
    }

    public function test_expenses_are_scoped_to_the_current_project(): void
    {
        $mine = $this->participation();
        TravelExpense::factory()->forParticipant($mine)->create();

        $otherProject = Project::factory()->for($this->organization)->create();
        $theirs = $this->participation(project: $otherProject, name: 'Other Person');
        TravelExpense::factory()->forParticipant($theirs)->create();

        $this->assertSame(2, DB::table('travel_expenses')->count());
        $this->assertSame(1, TravelExpense::query()->count());
    }

    public function test_list_page_loads_and_shows_the_expense(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->costing(120)->create([
            'from' => 'Madrid',
            'to' => 'Berlin',
        ]);

        Livewire::test(ListTravelExpenses::class)
            ->assertSuccessful()
            ->assertSee('Ana Torres')
            ->assertSee('Madrid');
    }

    public function test_finance_page_renders_over_http(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create();

        $this->get(TravelExpenseResource::getUrl(tenant: $this->project))
            ->assertSuccessful()
            ->assertSee('Travel expenses');
    }

    public function test_tabs_mirror_the_participant_countries(): void
    {
        $this->participation(countryId: $this->countryId('ES', 'Spain'));
        $this->participation(countryId: $this->countryId('IT', 'Italy'), name: 'Luca Rossi');

        $tabs = Livewire::test(ListTravelExpenses::class)->instance()->getTabs();

        $this->assertArrayHasKey('all', $tabs);
        $this->assertCount(3, $tabs);
    }

    public function test_tabs_render_through_the_published_view(): void
    {
        $this->participation(countryId: $this->countryId('ES', 'Spain'));

        $this->assertFileExists(resource_path('views/vendor/filament-schemas/components/tabs.blade.php'));
        $this->assertTrue(Tabs::hasPublishedEmbeddedViewOverride('filament-schemas::components.tabs'));

        Livewire::test(ListTravelExpenses::class)
            ->assertSeeHtml('fi-sc-tabs')
            ->assertSeeHtml('fi-tabs-item')
            ->assertSeeHtml('fi-tabs-item-label')
            ->assertSee('Spain')
            ->assertSeeHtml("wire:click=\"\$set('activeTab', 'all')\"");
    }

    public function test_country_tab_only_counts_expenses_of_that_country(): void
    {
        $spain = $this->participation(countryId: $this->countryId('ES', 'Spain'));
        $italy = $this->participation(countryId: $this->countryId('IT', 'Italy'), name: 'Luca Rossi');

        TravelExpense::factory()->forParticipant($spain)->count(2)->create();
        TravelExpense::factory()->forParticipant($italy)->create();

        $this->assertSame(1, TravelExpense::query()
            ->whereRelation('projectParticipant', 'country_id', $this->countryId('IT', 'Italy'))
            ->count());
    }

    public function test_expense_is_created_through_the_modal_form(): void
    {
        $participation = $this->participation();

        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'participable_id' => 'p:'.$participation->participable_id,
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Lisbon',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '45.90',
                'currency' => 'EUR',
                'cost_eur' => '45.90',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('travel_expenses', [
            'project_participant_id' => $participation->id,
            'from' => 'Lisbon',
            'to' => 'Porto',
            'cost_eur' => '45.90',
        ]);
    }

    public function test_create_and_edit_run_through_the_wizard_steps_in_order(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $page = Livewire::test(ListTravelExpenses::class)->instance();

        $this->assertTrue($page->getAction('create')->isWizard());
        $this->assertTrue($page->getTable()->getAction('edit')->record($expense)->isWizard());
        $this->assertSame(
            ['Proof of travel', 'Proof of payment', 'Travel details', 'Travel', 'Cost', 'Review'],
            array_map(fn (Step $step): string => $step->getLabel(), TravelExpenseForm::steps()),
        );
    }

    public function test_every_wizard_step_heads_its_section_with_its_own_label(): void
    {
        $steps = $this->mountedCreateWizard()
            ->getChildSchemas(withHidden: true)['default']
            ->getComponents(withHidden: true);

        $this->assertCount(6, $steps);

        foreach ($steps as $step) {
            $section = $step->getChildSchemas(withHidden: true)['default']->getComponents(withHidden: true)[0];

            $this->assertSame($step->getLabel(), $section->getHeading());
            $this->assertNotSame($section->getHeading(), $section->getLabel(), $step->getLabel().' has no description');
        }
    }

    public function test_euro_cost_is_hidden_and_mirrors_cost_when_the_currency_is_euro(): void
    {
        $participation = $this->participation();

        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'participable_id' => 'p:'.$participation->participable_id,
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Lisbon',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '45.90',
                'currency' => 'EUR',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('travel_expenses', [
            'cost' => '45.90',
            'currency' => 'EUR',
            'cost_eur' => '45.90',
        ]);
    }

    public function test_euro_cost_is_asked_for_when_the_currency_is_not_euro(): void
    {
        $participation = $this->participation();

        DB::table('currencies')->insert([
            'country_id' => $this->countryId(),
            'name' => 'Swedish krona',
            'code' => 'SEK',
            'precision' => 2,
            'symbol' => 'kr',
            'symbol_native' => 'kr',
            'symbol_first' => 1,
            'decimal_mark' => ',',
            'thousands_separator' => ' ',
        ]);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'participable_id' => 'p:'.$participation->participable_id,
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Stockholm',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '1200.00',
                'currency' => 'SEK',
                'cost_eur' => '104.50',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('travel_expenses', [
            'cost' => '1200.00',
            'currency' => 'SEK',
            'cost_eur' => '104.50',
        ]);
    }

    public function test_create_wizard_offers_add_and_add_another(): void
    {
        $submit = (string) $this->mountedCreateWizard()->getSubmitAction();

        $this->assertStringContainsString('callMountedAction({ another: true })', $submit);
        $this->assertStringContainsString('Add another', $submit);
        $this->assertStringContainsString('Add expense', $submit);
    }

    private function mountedCreateWizard(): Wizard
    {
        $wizard = Livewire::test(ListTravelExpenses::class)
            ->mountAction('create')
            ->instance()
            ->getSchema('mountedActionSchema0')
            ->getComponents(withHidden: true)[0];

        $this->assertInstanceOf(Wizard::class, $wizard);

        return $wizard;
    }

    public function test_review_step_summarises_what_was_entered(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->costing(45.9)->create([
            'travel_type' => 'departure',
            'transportation_type' => 'train',
            'from' => 'Lisbon',
            'to' => 'Porto',
            'date' => '2026-04-12',
            'currency' => 'EUR',
        ]);

        $schema = Livewire::test(ListTravelExpenses::class)
            ->mountTableAction('edit', $expense)
            ->instance()
            ->getSchema('mountedActionSchema0');

        $review = [];

        foreach ($schema->getFlatComponents(withHidden: true) as $component) {
            if ($component instanceof TextEntry) {
                $review[$component->getName()] = $component->getState();
            }
        }

        $this->assertSame([
            'review_participant' => 'Ana Torres',
            'review_travel_type' => 'Departure',
            'review_transportation_type' => 'Train',
            'review_route' => 'Lisbon → Porto',
            'review_date' => 'Apr 12, 2026',
            'review_cost' => 'EUR 45.90',
            'review_cost_eur' => '€45.90',
            'review_proof' => '0 payment file(s), 0 journey file(s)',
        ], $review);
    }

    public function test_expense_cannot_be_created_without_a_participant(): void
    {
        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Lisbon',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '45.90',
                'currency' => 'EUR',
                'cost_eur' => '45.90',
            ])
            ->assertHasActionErrors(['participable_id']);

        $this->assertDatabaseCount('travel_expenses', 0);
    }

    public function test_someone_not_yet_on_this_project_needs_origin_details(): void
    {
        $otherProject = Project::factory()->for($this->organization)->create();
        $theirs = $this->participation(project: $otherProject, name: 'Other Person');

        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'participable_id' => 'p:'.$theirs->participable_id,
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Lisbon',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '45.90',
                'currency' => 'EUR',
                'cost_eur' => '45.90',
            ])
            ->assertHasActionErrors(['country_id', 'sending_organization_id']);

        $this->assertDatabaseCount('travel_expenses', 0);
    }

    public function test_an_authorised_user_can_register_an_expense_for_someone_new_to_the_project(): void
    {
        $newcomer = Participant::factory()->create(['name' => 'Nina Novak']);
        $sending = ParticipantOrganization::create(['name' => 'Instituto']);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'participable_id' => 'p:'.$newcomer->id,
                'country_id' => $this->countryId(),
                'sending_organization_id' => 'po:'.$sending->id,
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Lisbon',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '45.90',
                'currency' => 'EUR',
                'cost_eur' => '45.90',
            ])
            ->assertHasNoActionErrors();

        $participation = ProjectParticipant::query()
            ->where('project_id', $this->project->id)
            ->where('participable_type', Participant::class)
            ->where('participable_id', $newcomer->id)
            ->firstOrFail();

        $this->assertDatabaseHas('travel_expenses', [
            'project_participant_id' => $participation->id,
            'from' => 'Lisbon',
        ]);
    }

    public function test_an_expense_can_be_registered_for_a_person_not_yet_in_the_app(): void
    {
        $sending = ParticipantOrganization::create(['name' => 'Instituto']);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'participable_id' => 'pending:'.md5('Zoe Fresh'),
                '_pending_participable' => ['name' => 'Zoe Fresh'],
                '_pending_source' => 'name',
                'participable' => ['name' => 'Zoe Fresh', 'email' => 'zoe@example.test'],
                'country_id' => $this->countryId(),
                'sending_organization_id' => 'po:'.$sending->id,
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Lisbon',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '45.90',
                'currency' => 'EUR',
                'cost_eur' => '45.90',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('participants', [
            'name' => 'Zoe Fresh',
            'email' => 'zoe@example.test',
        ]);
        $this->assertDatabaseCount('travel_expenses', 1);
    }

    public function test_country_limits_action_saves_and_clears_limits(): void
    {
        $countryId = $this->countryId();
        $this->participation(countryId: $countryId);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('countryLimits', ['limits' => [$countryId => '250.50']]);

        $this->assertDatabaseHas('country_limits', [
            'project_id' => $this->project->id,
            'country_id' => $countryId,
            'amount_eur' => '250.50',
        ]);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('countryLimits', ['limits' => [$countryId => null]]);

        $this->assertDatabaseMissing('country_limits', [
            'project_id' => $this->project->id,
            'country_id' => $countryId,
        ]);
    }

    public function test_country_limits_modal_prefills_existing_values(): void
    {
        $countryId = $this->countryId();
        $this->participation(countryId: $countryId);

        CountryLimit::create([
            'project_id' => $this->project->id,
            'country_id' => $countryId,
            'amount_eur' => 410,
        ]);

        Livewire::test(ListTravelExpenses::class)
            ->mountAction('countryLimits')
            ->assertActionDataSet(['limits' => [$countryId => '410.00']])
            ->callMountedAction();

        $this->assertDatabaseHas('country_limits', [
            'project_id' => $this->project->id,
            'country_id' => $countryId,
            'amount_eur' => '410.00',
        ]);
    }

    public function test_reimbursement_recalculates_when_country_limits_change(): void
    {
        $countryId = $this->countryId();
        $participation = $this->participation(countryId: $countryId);
        $expense = TravelExpense::factory()->forParticipant($participation)->costing(300)->create();

        $badge = Livewire::test(Reimbursement::class, ['expense' => $expense->fresh()]);
        $this->assertStringContainsString('No limit set', $badge->html());

        Livewire::test(ListTravelExpenses::class)
            ->callAction('countryLimits', ['limits' => [$countryId => '275']])
            ->assertDispatched(Reimbursement::LIMITS_UPDATED);

        $badge->dispatch(Reimbursement::LIMITS_UPDATED);

        $this->assertStringContainsString('€300.00 of €275.00', $badge->html());
        $this->assertStringContainsString('fi-color-danger', $badge->html());
    }

    public function test_country_limit_is_unique_per_project_and_country(): void
    {
        $countryId = $this->countryId();

        CountryLimit::updateOrCreate(
            ['project_id' => $this->project->id, 'country_id' => $countryId],
            ['amount_eur' => 100],
        );
        CountryLimit::updateOrCreate(
            ['project_id' => $this->project->id, 'country_id' => $countryId],
            ['amount_eur' => 200],
        );

        $this->assertDatabaseCount('country_limits', 1);
        $this->assertDatabaseHas('country_limits', ['amount_eur' => '200.00']);
    }

    public function test_expense_stores_original_currency_alongside_the_euro_value(): void
    {
        $participation = $this->participation();

        $expense = TravelExpense::create([
            'project_participant_id' => $participation->id,
            'travel_type' => 'departure',
            'transportation_type' => 'flight',
            'from' => 'Oslo',
            'to' => 'Madrid',
            'date' => '2026-05-01',
            'cost' => 1200,
            'currency' => 'NOK',
            'cost_eur' => 103.45,
        ]);

        $this->assertSame('NOK', $expense->currency);
        $this->assertSame('1200.00', $expense->cost);
        $this->assertSame('103.45', $expense->cost_eur);
    }

    public function test_proof_collections_accept_multiple_files(): void
    {
        Storage::fake('public');

        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $expense->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
            ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);
        $expense->addMedia(UploadedFile::fake()->create('receipt.pdf', 10))
            ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);
        $expense->addMedia(UploadedFile::fake()->create('boarding-pass.pdf', 10))
            ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_JOURNEY);

        $this->assertCount(2, $expense->getMedia(TravelExpense::COLLECTION_PROOF_OF_PAYMENT));
        $this->assertCount(1, $expense->getMedia(TravelExpense::COLLECTION_PROOF_OF_JOURNEY));
    }

    public function test_bare_participant_cannot_view_the_finance_resource(): void
    {
        $outsider = User::factory()->create();
        $outsider->joinOrganization($this->organization);
        $outsider->joinProject($this->project, ProjectRole::Participant);

        $this->actingAs($outsider);

        $this->assertFalse(TravelExpenseResource::canViewAny());
        $this->assertFalse(TravelExpenseResource::canManageCountryLimits());
    }

    public function test_finance_permission_does_not_grant_country_limits(): void
    {
        $coordinator = User::factory()->create();
        $coordinator->joinOrganization($this->organization);
        $coordinator->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)
            ->givePermissionTo('create_travel_expense', 'update_travel_expense', 'delete_travel_expense');

        $this->actingAs($coordinator);

        $this->assertTrue(TravelExpenseResource::canViewAny());
        $this->assertFalse(TravelExpenseResource::canManageCountryLimits());
    }

    public function test_transport_renders_as_a_badge_beside_the_route(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create([
            'travel_type' => 'departure',
            'transportation_type' => 'flight',
            'from' => 'Madrid',
            'to' => 'Berlin',
        ]);

        Livewire::test(ListTravelExpenses::class)
            ->assertSee('Flight')
            ->assertSee('Departure')
            ->assertSee('Madrid → Berlin');

        $this->assertInstanceOf(
            ViewColumn::class,
            Livewire::test(ListTravelExpenses::class)->instance()->getTable()->getColumn('travel_type'),
        );
        $this->assertNotNull($expense->id);
    }

    public function test_reimbursement_is_hidden_without_the_country_limits_permission(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $coordinator = User::factory()->create();
        $coordinator->joinOrganization($this->organization);
        $coordinator->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)
            ->givePermissionTo('create_travel_expense', 'update_travel_expense', 'delete_travel_expense');
        $this->project->roleFor(ProjectRole::Participant)
            ->givePermissionTo('view_any_travel_expense');

        $this->actingAs($coordinator);

        $this->assertNull($this->reimbursementDescription($expense));
    }

    public function test_reimbursement_is_shown_to_an_org_admin(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $this->assertNotNull($this->reimbursementDescription($expense));
    }

    private function reimbursementDescription(TravelExpense $expense): ?string
    {
        $description = Livewire::test(ListTravelExpenses::class)
            ->instance()
            ->getTable()
            ->getColumn('cost_eur')
            ->record($expense->fresh())
            ->getDescriptionBelow();

        return $description === null ? null : (string) $description;
    }

    public function test_over_limit_is_measured_on_the_participant_total_not_a_single_leg(): void
    {
        $countryId = $this->countryId();
        $participation = $this->participation(countryId: $countryId);

        TravelExpense::factory()->forParticipant($participation)->costing(150)->create();
        TravelExpense::factory()->forParticipant($participation)->costing(150)->create();

        CountryLimit::create([
            'project_id' => $this->project->id,
            'country_id' => $countryId,
            'amount_eur' => 275,
        ]);

        $total = (float) $participation->travelExpenses()->sum('cost_eur');
        $limit = (float) $participation->travelExpenses()->first()->countryLimit->amount_eur;

        $this->assertSame(300.0, $total);
        $this->assertGreaterThan($limit, $total);
    }

    public function test_route_arrow_follows_the_travel_type(): void
    {
        $participation = $this->participation();

        $departure = TravelExpense::factory()->forParticipant($participation)
            ->create(['travel_type' => 'departure', 'from' => 'Madrid', 'to' => 'Berlin']);
        $return = TravelExpense::factory()->forParticipant($participation)
            ->create(['travel_type' => 'return', 'from' => 'Berlin', 'to' => 'Madrid']);

        Livewire::test(ListTravelExpenses::class)
            ->assertSee('Madrid → Berlin')
            ->assertSee('Berlin ← Madrid');

        $this->assertNotSame($departure->id, $return->id);
    }

    public function test_reimbursement_badge_is_success_under_the_limit_and_danger_over_it(): void
    {
        $countryId = $this->countryId();
        $under = $this->participation(countryId: $countryId);
        $over = $this->participation(countryId: $countryId, name: 'Big Spender');

        $underExpense = TravelExpense::factory()->forParticipant($under)->costing(100)->create();
        $overExpense = TravelExpense::factory()->forParticipant($over)->costing(400)->create();

        CountryLimit::create([
            'project_id' => $this->project->id,
            'country_id' => $countryId,
            'amount_eur' => 275,
        ]);

        $column = Livewire::test(ListTravelExpenses::class)->instance()->getTable()->getColumn('cost_eur');

        $this->assertStringContainsString(
            'fi-color-success',
            (string) $column->record($underExpense->fresh())->getDescriptionBelow(),
        );
        $this->assertStringContainsString(
            'fi-color-danger',
            (string) $column->record($overExpense->fresh())->getDescriptionBelow(),
        );
    }

    public function test_sorting_still_works_for_a_stacked_column(): void
    {
        $participation = $this->participation();

        $cheap = TravelExpense::factory()->forParticipant($participation)->costing(50)->create();
        $pricey = TravelExpense::factory()->forParticipant($participation)->costing(500)->create();

        Livewire::test(ListTravelExpenses::class)
            ->sortTable('cost_eur')
            ->assertCanSeeTableRecords([$cheap, $pricey], inOrder: true)
            ->sortTable('cost_eur', 'desc')
            ->assertCanSeeTableRecords([$pricey, $cheap], inOrder: true);
    }

    public function test_original_currency_is_a_tooltip_on_the_euro_column(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create([
            'cost' => '120.00',
            'currency' => 'SEK',
            'cost_eur' => '10.50',
        ]);

        $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();

        $this->assertNull($table->getColumn('cost'));
        $this->assertStringContainsString(
            'SEK',
            (string) $table->getColumn('cost_eur')->record($expense)->getTooltip(),
        );
    }

    public function test_reimbursement_column_reports_the_participant_total_against_the_limit(): void
    {
        $countryId = $this->countryId();
        $participation = $this->participation(countryId: $countryId);

        $first = TravelExpense::factory()->forParticipant($participation)->costing(150)->create();
        TravelExpense::factory()->forParticipant($participation)->costing(150)->create();

        CountryLimit::create([
            'project_id' => $this->project->id,
            'country_id' => $countryId,
            'amount_eur' => 275,
        ]);

        $this->assertStringContainsString('€300.00 of €275.00', $this->reimbursementDescription($first));
    }

    public function test_reimbursement_says_so_when_no_limit_is_set(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->costing(150)->create();

        $this->assertStringContainsString('No limit set', $this->reimbursementDescription($expense));
    }

    private function selfServiceUser(): ProjectParticipant
    {
        $user = User::factory()->create(['name' => 'Self Server']);
        $user->joinOrganization($this->organization);
        $user->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)
            ->givePermissionTo('create_travel_expense', 'update_travel_expense', 'delete_travel_expense');

        $sending = ParticipantOrganization::create(['name' => 'Employer']);
        $participation = $this->project->addParticipant($user, $this->countryId(), $sending);

        $this->actingAs($user);

        return $participation;
    }

    public function test_participant_without_view_all_only_sees_their_own_expenses(): void
    {
        $someoneElse = $this->participation(name: 'Other Traveller');
        $theirs = TravelExpense::factory()->forParticipant($someoneElse)->create(['from' => 'Reykjavik']);

        $own = $this->selfServiceUser();
        $mine = TravelExpense::factory()->forParticipant($own)->create(['from' => 'Madrid']);

        Livewire::test(ListTravelExpenses::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs])
            ->assertDontSee('Other Traveller')
            ->assertDontSee('Reykjavik');
    }

    public function test_participant_column_and_tabs_are_hidden_without_view_all(): void
    {
        $this->participation(countryId: $this->countryId('IT', 'Italy'), name: 'Other Traveller');
        $own = $this->selfServiceUser();
        TravelExpense::factory()->forParticipant($own)->create();

        $component = Livewire::test(ListTravelExpenses::class);

        $component->assertTableColumnHidden('projectParticipant.participable.name');
        $this->assertSame(['all'], array_keys($component->instance()->getTabs()));
    }

    public function test_participant_column_is_visible_with_view_all(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create();

        Livewire::test(ListTravelExpenses::class)
            ->assertTableColumnVisible('projectParticipant.participable.name');
    }

    public function test_self_service_create_forces_the_users_own_participation(): void
    {
        $someoneElse = $this->participation(name: 'Other Traveller');
        $own = $this->selfServiceUser();

        Livewire::test(ListTravelExpenses::class)
            ->callAction('create', [
                'project_participant_id' => $someoneElse->id,
                'travel_type' => 'departure',
                'transportation_type' => 'train',
                'from' => 'Lisbon',
                'to' => 'Porto',
                'date' => '2026-04-12',
                'cost' => '45.90',
                'currency' => 'EUR',
                'cost_eur' => '45.90',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('travel_expenses', [
            'from' => 'Lisbon',
            'project_participant_id' => $own->id,
        ]);
        $this->assertDatabaseMissing('travel_expenses', [
            'project_participant_id' => $someoneElse->id,
        ]);
    }

    public function test_participant_with_no_participation_cannot_see_other_expenses(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create();

        $stranger = User::factory()->create();
        $stranger->joinOrganization($this->organization);
        $stranger->joinProject($this->project, ProjectRole::Participant);

        $this->project->roleFor(ProjectRole::Participant)
            ->givePermissionTo('create_travel_expense', 'update_travel_expense', 'delete_travel_expense');

        $this->actingAs($stranger);

        $this->assertTrue(TravelExpenseResource::canViewAny());

        Livewire::test(ListTravelExpenses::class)
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords(TravelExpense::query()->get());
    }

    public function test_travel_type_filter_narrows_the_table(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create(['travel_type' => 'departure', 'from' => 'Vigo']);
        TravelExpense::factory()->forParticipant($participation)->create(['travel_type' => 'return', 'from' => 'Tallinn']);

        Livewire::test(ListTravelExpenses::class)
            ->assertCanSeeTableRecords(TravelExpense::query()->get())
            ->filterTable('group', ['travel_type' => 'departure'])
            ->assertCanSeeTableRecords(TravelExpense::query()->where('travel_type', 'departure')->get())
            ->assertCanNotSeeTableRecords(TravelExpense::query()->where('travel_type', 'return')->get());
    }

    public function test_search_filter_matches_participant_name_and_route(): void
    {
        $ana = $this->participation();
        $luca = $this->participation(name: 'Luca Rossi');

        $anaExpense = TravelExpense::factory()->forParticipant($ana)->create(['from' => 'Madrid', 'to' => 'Berlin']);
        $lucaExpense = TravelExpense::factory()->forParticipant($luca)->create(['from' => 'Rome', 'to' => 'Vienna']);

        Livewire::test(ListTravelExpenses::class)
            ->searchTable('Luca')
            ->assertCanSeeTableRecords([$lucaExpense])
            ->assertCanNotSeeTableRecords([$anaExpense])
            ->searchTable('Madrid')
            ->assertCanSeeTableRecords([$anaExpense])
            ->assertCanNotSeeTableRecords([$lucaExpense]);
    }

    public function test_date_filter_narrows_to_the_range(): void
    {
        $participation = $this->participation();

        $early = TravelExpense::factory()->forParticipant($participation)->create(['date' => '2026-01-10']);
        $late = TravelExpense::factory()->forParticipant($participation)->create(['date' => '2026-06-20']);

        Livewire::test(ListTravelExpenses::class)
            ->filterTable('group', ['date_from' => '2026-05-01'])
            ->assertCanSeeTableRecords([$late])
            ->assertCanNotSeeTableRecords([$early])
            ->filterTable('group', ['date_from' => null, 'date_until' => '2026-02-01'])
            ->assertCanSeeTableRecords([$early])
            ->assertCanNotSeeTableRecords([$late]);
    }

    public function test_proof_filter_separates_documented_from_undocumented_expenses(): void
    {
        Storage::fake('public');

        $participation = $this->participation();
        $documented = TravelExpense::factory()->forParticipant($participation)->create();
        $bare = TravelExpense::factory()->forParticipant($participation)->create();

        $documented->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
            ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);

        Livewire::test(ListTravelExpenses::class)
            ->filterTable('group', ['proof_of_payment' => true])
            ->assertCanSeeTableRecords([$documented])
            ->assertCanNotSeeTableRecords([$bare])
            ->filterTable('group', ['proof_of_payment' => false])
            ->assertCanSeeTableRecords([$documented, $bare]);
    }

    public function test_proof_of_journey_toggle_ignores_payment_proof(): void
    {
        Storage::fake('public');

        $participation = $this->participation();
        $paymentOnly = TravelExpense::factory()->forParticipant($participation)->create();

        $paymentOnly->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
            ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);

        Livewire::test(ListTravelExpenses::class)
            ->filterTable('group', ['proof_of_journey' => true])
            ->assertCanNotSeeTableRecords([$paymentOnly]);
    }

    public function test_type_filters_default_to_any_and_do_not_narrow_the_table(): void
    {
        $participation = $this->participation();
        $expenses = TravelExpense::factory()->forParticipant($participation)->count(3)->create();

        Livewire::test(ListTravelExpenses::class)
            ->assertCanSeeTableRecords($expenses)
            ->filterTable('group', ['travel_type' => EnumSelect::ANY, 'transportation_type' => EnumSelect::ANY])
            ->assertCanSeeTableRecords($expenses);
    }

    public function test_filter_group_badge_ignores_defaults_and_untoggled_switches(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create();

        $component = Livewire::test(ListTravelExpenses::class);

        $this->assertSame(0, $this->filterGroupBadge($component));

        $component->filterTable('group', ['travel_type' => 'departure']);

        $this->assertSame(1, $this->filterGroupBadge($component));
    }

    private function filterGroupBadge(Testable $component): int
    {
        foreach ($component->instance()->getTable()->getFilter('group')->getSchema()->getFlatComponents(withHidden: true) as $child) {
            if ($child instanceof FilterGroupDropdown) {
                return $child->getActiveCount();
            }
        }

        return -1;
    }

    public function test_sort_defaults_to_participant_name_and_offers_no_travel_option(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create();

        $sort = null;

        foreach (Livewire::test(ListTravelExpenses::class)->instance()->getTable()->getFilter('sort')->getSchema()->getFlatComponents(withHidden: true) as $child) {
            if ($child->getName() === 'sort') {
                $sort = $child;
            }
        }

        $this->assertSame('projectParticipant.participable.name', $sort?->getDefaultState());
        $this->assertSame(
            ['projectParticipant.participable.name', 'date', 'cost_eur'],
            array_keys($sort->getOptions()),
        );
    }

    public function test_expenses_sort_by_participant_name_across_both_participable_types(): void
    {
        $zoe = $this->participation(name: 'Zoe Last');
        $ana = $this->participation(name: 'Ana First');

        $user = User::factory()->create(['name' => 'Mid User']);
        $user->joinOrganization($this->organization);
        $mid = $this->project->addParticipant($user, $this->countryId(), ParticipantOrganization::create(['name' => 'Employer']));

        $last = TravelExpense::factory()->forParticipant($zoe)->create();
        $first = TravelExpense::factory()->forParticipant($ana)->create();
        $middle = TravelExpense::factory()->forParticipant($mid)->create();

        Livewire::test(ListTravelExpenses::class)
            ->sortTable('projectParticipant.participable.name')
            ->assertCanSeeTableRecords([$first, $middle, $last], inOrder: true)
            ->sortTable('projectParticipant.participable.name', 'desc')
            ->assertCanSeeTableRecords([$last, $middle, $first], inOrder: true);
    }

    public function test_column_manager_is_a_filter_not_a_toolbar_trigger(): void
    {
        $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();

        $this->assertFalse($table->hasColumnManager());
        $this->assertArrayHasKey('columns', $table->getFilters());
    }

    public function test_every_column_is_toggleable_so_the_manager_renders_checkboxes(): void
    {
        $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();

        $this->assertTrue($table->hasToggleableColumns());

        foreach ($table->getColumns() as $column) {
            $this->assertTrue($column->isToggleable(), $column->getName().' is not toggleable');
        }
    }

    public function test_proof_columns_report_attachments_and_start_hidden(): void
    {
        Storage::fake('public');

        $participation = $this->participation();
        $documented = TravelExpense::factory()->forParticipant($participation)->create();
        $bare = TravelExpense::factory()->forParticipant($participation)->create();

        $documented->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
            ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);

        $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();

        foreach (['proof_of_payment', 'proof_of_journey'] as $name) {
            $this->assertTrue($table->getColumn($name)->isToggledHiddenByDefault(), "{$name} should start hidden");
        }

        $payment = $table->getColumn('proof_of_payment');

        $this->assertTrue($payment->record($documented->fresh())->getState());
        $this->assertFalse($payment->record($bare->fresh())->getState());
        $this->assertFalse($table->getColumn('proof_of_journey')->record($documented->fresh())->getState());
    }

    public function test_enum_filter_options_carry_the_enum_icons(): void
    {
        $options = EnumSelect::make('travel_type', TravelType::class, 'Any travel', 'lucide-plane-takeoff')
            ->getOptions();

        foreach ([EnumSelect::ANY, 'departure', 'return'] as $value) {
            $this->assertStringContainsString('<svg', $options[$value], "{$value} has no icon");
        }

        $this->assertStringContainsString('Departure', $options['departure']);
        $this->assertStringContainsString('Return', $options['return']);
        $this->assertNotSame($options['departure'], $options['return']);
    }

    public function test_all_tab_groups_expenses_by_country(): void
    {
        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();
        $group = $table->getDefaultGroup();

        $this->assertSame('projectParticipant.country.name', $group?->getId());
        $this->assertSame('🇪🇸 Spain', $group->getTitle($expense->fresh()));
        $this->assertFalse($group->isTitlePrefixedWithLabel());
    }

    public function test_travel_and_transport_badges_are_a_column_apart_from_the_route(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create([
            'travel_type' => 'departure',
            'transportation_type' => 'flight',
            'from' => 'Madrid',
            'to' => 'Berlin',
        ]);

        $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();

        $this->assertInstanceOf(ViewColumn::class, $table->getColumn('travel_type'));
        $this->assertInstanceOf(ViewColumn::class, $table->getColumn('from'));

        Livewire::test(ListTravelExpenses::class)
            ->assertSee('Departure')
            ->assertSee('Flight')
            ->assertSee('Madrid → Berlin');
    }

    public function test_seeder_only_creates_expenses_for_participant_participations(): void
    {
        $participation = $this->participation();

        $user = User::factory()->create();
        $sending = ParticipantOrganization::create(['name' => 'Employer']);
        $userParticipation = $this->project->addParticipant($user, $this->countryId(), $sending);

        $this->seed(TravelExpenseSeeder::class);

        $this->assertSame(2, TravelExpense::query()
            ->where('project_participant_id', $participation->id)
            ->count());
        $this->assertSame(0, TravelExpense::query()
            ->where('project_participant_id', $userParticipation->id)
            ->count());
        $this->assertDatabaseHas('country_limits', ['project_id' => $this->project->id]);
    }

    public function test_export_writes_the_current_project_rows(): void
    {
        Storage::fake('public');

        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->costing(120)->create([
            'from' => 'Madrid',
            'to' => 'Berlin',
            'travel_type' => 'departure',
            'transportation_type' => 'flight',
        ]);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('export')
            ->assertHasNoActionErrors();

        $export = Export::query()->latest('id')->firstOrFail();

        $this->assertSame(1, $export->successful_rows);
        $this->assertStringContainsString('Ana Torres', $this->exportedCsv($export));
        $this->assertStringContainsString('Madrid', $this->exportedCsv($export));
    }

    public function test_export_reports_whether_proof_is_attached(): void
    {
        Storage::fake('public');

        $participation = $this->participation();
        $expense = TravelExpense::factory()->forParticipant($participation)->create();

        $expense->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
            ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('export')
            ->assertHasNoActionErrors();

        $csv = $this->exportedCsv(Export::query()->latest('id')->firstOrFail());

        $this->assertStringContainsString('Proof of payment', $csv);
        $this->assertStringContainsString('Proof of journey', $csv);
        $this->assertMatchesRegularExpression('/Yes,+\s*No/', str_replace('"', '', $csv));
    }

    public function test_export_does_not_leak_another_projects_expenses(): void
    {
        Storage::fake('public');

        $mine = $this->participation();
        TravelExpense::factory()->forParticipant($mine)->create(['from' => 'Madrid']);

        $otherProject = Project::factory()->for($this->organization)->create();
        $theirs = $this->participation(project: $otherProject, name: 'Hidden Person');
        TravelExpense::factory()->forParticipant($theirs)->create(['from' => 'Reykjavik']);

        Livewire::test(ListTravelExpenses::class)
            ->callAction('export')
            ->assertHasNoActionErrors();

        $export = Export::query()->latest('id')->firstOrFail();
        $csv = $this->exportedCsv($export);

        $this->assertSame(1, $export->successful_rows);
        $this->assertStringContainsString('Ana Torres', $csv);
        $this->assertStringNotContainsString('Hidden Person', $csv);
        $this->assertStringNotContainsString('Reykjavik', $csv);
    }

    public function test_export_escapes_formula_injection_in_text_columns(): void
    {
        Storage::fake('public');

        $participation = $this->participation(name: '=SUM(A1:A9)');
        TravelExpense::factory()->forParticipant($participation)->create();

        Livewire::test(ListTravelExpenses::class)
            ->callAction('export')
            ->assertHasNoActionErrors();

        $csv = $this->exportedCsv(Export::query()->latest('id')->firstOrFail());

        $this->assertStringContainsString("'=SUM(A1:A9)", $csv);
    }

    private function exportedCsv(Export $export): string
    {
        $directory = $export->getFileDirectory();

        return collect(Storage::disk($export->file_disk)->files($directory))
            ->map(fn (string $path): string => Storage::disk($export->file_disk)->get($path))
            ->implode("\n");
    }

    public function test_deleting_a_participation_removes_its_expenses(): void
    {
        $participation = $this->participation();
        TravelExpense::factory()->forParticipant($participation)->create();

        $participation->delete();

        $this->assertSame(0, DB::table('travel_expenses')->count());
    }
}

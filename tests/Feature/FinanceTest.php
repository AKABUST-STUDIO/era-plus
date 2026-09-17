<?php

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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
    $this->project = Project::factory()->for($this->organization)->create();
    $this->user->joinProject($this->project, ProjectRole::Admin);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('project'));
    Filament::setTenant($this->project);
    URL::defaults(['organization' => $this->organization->slug]);
});

function financeCountryId(string $iso2 = 'ES', string $name = 'Spain'): int
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

function financeParticipation(Project $project, ?int $countryId = null, string $name = 'Ana Torres'): ProjectParticipant
{
    $participant = Participant::factory()->create(['name' => $name]);
    $sending = ParticipantOrganization::create(['name' => 'Universidad']);

    return $project->addParticipant($participant, $countryId ?? financeCountryId(), $sending);
}

function financeMountedCreateWizard($testCase): Wizard
{
    $wizard = Livewire::test(ListTravelExpenses::class)
        ->mountAction('create')
        ->instance()
        ->getSchema('mountedActionSchema0')
        ->getComponents(withHidden: true)[0];

    $testCase->assertInstanceOf(Wizard::class, $wizard);

    return $wizard;
}

function financeReimbursementDescription(TravelExpense $expense): ?string
{
    $description = Livewire::test(ListTravelExpenses::class)
        ->instance()
        ->getTable()
        ->getColumn('cost_eur')
        ->record($expense->fresh())
        ->getDescriptionBelow();

    return $description === null ? null : (string) $description;
}

function financeSelfServiceUser(Organization $organization, Project $project): ProjectParticipant
{
    $user = User::factory()->create(['name' => 'Self Server']);
    $user->joinOrganization($organization);
    $user->joinProject($project, ProjectRole::Participant);

    $project->roleFor(ProjectRole::Participant)
        ->givePermissionTo('create_travel_expense', 'update_travel_expense', 'delete_travel_expense');

    $sending = ParticipantOrganization::create(['name' => 'Employer']);
    $participation = $project->addParticipant($user, financeCountryId(), $sending);

    test()->actingAs($user);

    return $participation;
}

function financeFilterGroupBadge(Testable $component): int
{
    foreach ($component->instance()->getTable()->getFilter('group')->getSchema()->getFlatComponents(withHidden: true) as $child) {
        if ($child instanceof FilterGroupDropdown) {
            return $child->getActiveCount();
        }
    }

    return -1;
}

function financeExportedCsv(Export $export): string
{
    $directory = $export->getFileDirectory();

    return collect(Storage::disk($export->file_disk)->files($directory))
        ->map(fn (string $path): string => Storage::disk($export->file_disk)->get($path))
        ->implode("\n");
}

test('travel expenses table has no project id column', function (): void {
    $this->assertFalse(Schema::hasColumn('travel_expenses', 'project_id'));
    $this->assertTrue(Schema::hasColumn('travel_expenses', 'project_participant_id'));
});

test('expense resolves its project through the participation', function (): void {
    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $this->assertSame($this->project->id, $expense->projectParticipant->project_id);
    $this->assertTrue($this->project->travelExpenses->contains($expense));
});

test('expense links to the country limit through the participant country', function (): void {
    $countryId = financeCountryId();
    $participation = financeParticipation($this->project, countryId: $countryId);
    $expense = TravelExpense::factory()->forParticipant($participation)->costing(300)->create();

    CountryLimit::create([
        'project_id' => $this->project->id,
        'country_id' => $countryId,
        'amount_eur' => 275,
    ]);

    $this->assertNotNull($expense->fresh()->countryLimit);
    $this->assertSame('275.00', $expense->fresh()->countryLimit->amount_eur);
});

test('country limit of another project does not leak', function (): void {
    $countryId = financeCountryId();
    $participation = financeParticipation($this->project, countryId: $countryId);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $otherProject = Project::factory()->for($this->organization)->create();
    CountryLimit::create([
        'project_id' => $otherProject->id,
        'country_id' => $countryId,
        'amount_eur' => 999,
    ]);

    $this->assertNull($expense->fresh()->countryLimit);
});

test('expenses are scoped to the current project', function (): void {
    $mine = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($mine)->create();

    $otherProject = Project::factory()->for($this->organization)->create();
    $theirs = financeParticipation($otherProject, name: 'Other Person');
    TravelExpense::factory()->forParticipant($theirs)->create();

    $this->assertSame(2, DB::table('travel_expenses')->count());
    $this->assertSame(1, TravelExpense::query()->count());
});

test('list page loads and shows the expense', function (): void {
    $participation = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($participation)->costing(120)->create([
        'from' => 'Madrid',
        'to' => 'Berlin',
    ]);

    Livewire::test(ListTravelExpenses::class)
        ->assertSuccessful()
        ->assertSee('Ana Torres')
        ->assertSee('Madrid');
});

test('finance page renders over http', function (): void {
    $participation = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($participation)->create();

    $this->get(TravelExpenseResource::getUrl(tenant: $this->project))
        ->assertSuccessful()
        ->assertSee('Travel expenses');
});

test('tabs mirror the participant countries', function (): void {
    financeParticipation($this->project, countryId: financeCountryId('ES', 'Spain'));
    financeParticipation($this->project, countryId: financeCountryId('IT', 'Italy'), name: 'Luca Rossi');

    $tabs = Livewire::test(ListTravelExpenses::class)->instance()->getTabs();

    $this->assertArrayHasKey('all', $tabs);
    $this->assertCount(3, $tabs);
});

test('tabs render through the published view', function (): void {
    financeParticipation($this->project, countryId: financeCountryId('ES', 'Spain'));

    $this->assertFileExists(resource_path('views/vendor/filament-schemas/components/tabs.blade.php'));
    $this->assertTrue(Tabs::hasPublishedEmbeddedViewOverride('filament-schemas::components.tabs'));

    Livewire::test(ListTravelExpenses::class)
        ->assertSeeHtml('fi-sc-tabs')
        ->assertSeeHtml('fi-tabs-item')
        ->assertSeeHtml('fi-tabs-item-label')
        ->assertSee('Spain')
        ->assertSeeHtml("wire:click=\"\$set('activeTab', 'all')\"");
});

test('country tab only counts expenses of that country', function (): void {
    $spain = financeParticipation($this->project, countryId: financeCountryId('ES', 'Spain'));
    $italy = financeParticipation($this->project, countryId: financeCountryId('IT', 'Italy'), name: 'Luca Rossi');

    TravelExpense::factory()->forParticipant($spain)->count(2)->create();
    TravelExpense::factory()->forParticipant($italy)->create();

    $this->assertSame(1, TravelExpense::query()
        ->whereRelation('projectParticipant', 'country_id', financeCountryId('IT', 'Italy'))
        ->count());
});

test('expense is created through the modal form', function (): void {
    $participation = financeParticipation($this->project);

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
});

test('create and edit run through the wizard steps in order', function (): void {
    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $page = Livewire::test(ListTravelExpenses::class)->instance();

    $this->assertTrue($page->getAction('create')->isWizard());
    $this->assertTrue($page->getTable()->getAction('edit')->record($expense)->isWizard());
    $this->assertSame(
        ['Proof of travel', 'Proof of payment', 'Travel details', 'Travel', 'Cost', 'Review'],
        array_map(fn (Step $step): string => $step->getLabel(), TravelExpenseForm::steps()),
    );
});

test('every wizard step heads its section with its own label', function (): void {
    $steps = financeMountedCreateWizard($this)
        ->getChildSchemas(withHidden: true)['default']
        ->getComponents(withHidden: true);

    $this->assertCount(6, $steps);

    foreach ($steps as $step) {
        $section = $step->getChildSchemas(withHidden: true)['default']->getComponents(withHidden: true)[0];

        $this->assertSame($step->getLabel(), $section->getHeading());
        $this->assertNotSame($section->getHeading(), $section->getLabel(), $step->getLabel().' has no description');
    }
});

test('euro cost is hidden and mirrors cost when the currency is euro', function (): void {
    $participation = financeParticipation($this->project);

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
});

test('euro cost is asked for when the currency is not euro', function (): void {
    $participation = financeParticipation($this->project);

    DB::table('currencies')->insert([
        'country_id' => financeCountryId(),
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
});

test('create wizard offers add and add another', function (): void {
    $submit = (string) financeMountedCreateWizard($this)->getSubmitAction();

    $this->assertStringContainsString('callMountedAction({ another: true })', $submit);
    $this->assertStringContainsString('Add another', $submit);
    $this->assertStringContainsString('Add expense', $submit);
});

test('review step summarises what was entered', function (): void {
    $participation = financeParticipation($this->project);
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
});

test('expense cannot be created without a participant', function (): void {
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
});

test('someone not yet on this project needs origin details', function (): void {
    $otherProject = Project::factory()->for($this->organization)->create();
    $theirs = financeParticipation($otherProject, name: 'Other Person');

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
});

test('an authorised user can register an expense for someone new to the project', function (): void {
    $newcomer = Participant::factory()->create(['name' => 'Nina Novak']);
    $sending = ParticipantOrganization::create(['name' => 'Instituto']);

    Livewire::test(ListTravelExpenses::class)
        ->callAction('create', [
            'participable_id' => 'p:'.$newcomer->id,
            'country_id' => financeCountryId(),
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
});

test('an expense can be registered for a person not yet in the app', function (): void {
    $sending = ParticipantOrganization::create(['name' => 'Instituto']);

    Livewire::test(ListTravelExpenses::class)
        ->callAction('create', [
            'participable_id' => 'pending:'.md5('Zoe Fresh'),
            '_pending_participable' => ['name' => 'Zoe Fresh'],
            '_pending_source' => 'name',
            'participable' => ['name' => 'Zoe Fresh', 'email' => 'zoe@example.test'],
            'country_id' => financeCountryId(),
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
});

test('country limits action saves and clears limits', function (): void {
    $countryId = financeCountryId();
    financeParticipation($this->project, countryId: $countryId);

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
});

test('country limits modal prefills existing values', function (): void {
    $countryId = financeCountryId();
    financeParticipation($this->project, countryId: $countryId);

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
});

test('reimbursement recalculates when country limits change', function (): void {
    $countryId = financeCountryId();
    $participation = financeParticipation($this->project, countryId: $countryId);
    $expense = TravelExpense::factory()->forParticipant($participation)->costing(300)->create();

    $badge = Livewire::test(Reimbursement::class, ['expense' => $expense->fresh()]);
    $this->assertStringContainsString('No limit set', $badge->html());

    Livewire::test(ListTravelExpenses::class)
        ->callAction('countryLimits', ['limits' => [$countryId => '275']])
        ->assertDispatched(Reimbursement::LIMITS_UPDATED);

    $badge->dispatch(Reimbursement::LIMITS_UPDATED);

    $this->assertStringContainsString('€300.00 of €275.00', $badge->html());
    $this->assertStringContainsString('fi-color-danger', $badge->html());
});

test('country limit is unique per project and country', function (): void {
    $countryId = financeCountryId();

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
});

test('expense stores original currency alongside the euro value', function (): void {
    $participation = financeParticipation($this->project);

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
});

test('proof collections accept multiple files', function (): void {
    Storage::fake('public');

    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $expense->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
        ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);
    $expense->addMedia(UploadedFile::fake()->create('receipt.pdf', 10))
        ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);
    $expense->addMedia(UploadedFile::fake()->create('boarding-pass.pdf', 10))
        ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_JOURNEY);

    $this->assertCount(2, $expense->getMedia(TravelExpense::COLLECTION_PROOF_OF_PAYMENT));
    $this->assertCount(1, $expense->getMedia(TravelExpense::COLLECTION_PROOF_OF_JOURNEY));
});

test('bare participant cannot view the finance resource', function (): void {
    $outsider = User::factory()->create();
    $outsider->joinOrganization($this->organization);
    $outsider->joinProject($this->project, ProjectRole::Participant);

    $this->actingAs($outsider);

    $this->assertFalse(TravelExpenseResource::canViewAny());
    $this->assertFalse(TravelExpenseResource::canManageCountryLimits());
});

test('finance permission does not grant country limits', function (): void {
    $coordinator = User::factory()->create();
    $coordinator->joinOrganization($this->organization);
    $coordinator->joinProject($this->project, ProjectRole::Participant);

    $this->project->roleFor(ProjectRole::Participant)
        ->givePermissionTo('create_travel_expense', 'update_travel_expense', 'delete_travel_expense');

    $this->actingAs($coordinator);

    $this->assertTrue(TravelExpenseResource::canViewAny());
    $this->assertFalse(TravelExpenseResource::canManageCountryLimits());
});

test('transport renders as a badge beside the route', function (): void {
    $participation = financeParticipation($this->project);
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
});

test('reimbursement is hidden without the country limits permission', function (): void {
    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $coordinator = User::factory()->create();
    $coordinator->joinOrganization($this->organization);
    $coordinator->joinProject($this->project, ProjectRole::Participant);

    $this->project->roleFor(ProjectRole::Participant)
        ->givePermissionTo('create_travel_expense', 'update_travel_expense', 'delete_travel_expense');
    $this->project->roleFor(ProjectRole::Participant)
        ->givePermissionTo('view_any_travel_expense');

    $this->actingAs($coordinator);

    $this->assertNull(financeReimbursementDescription($expense));
});

test('reimbursement is shown to an org admin', function (): void {
    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $this->assertNotNull(financeReimbursementDescription($expense));
});

test('over limit is measured on the participant total not a single leg', function (): void {
    $countryId = financeCountryId();
    $participation = financeParticipation($this->project, countryId: $countryId);

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
});

test('route arrow follows the travel type', function (): void {
    $participation = financeParticipation($this->project);

    $departure = TravelExpense::factory()->forParticipant($participation)
        ->create(['travel_type' => 'departure', 'from' => 'Madrid', 'to' => 'Berlin']);
    $return = TravelExpense::factory()->forParticipant($participation)
        ->create(['travel_type' => 'return', 'from' => 'Berlin', 'to' => 'Madrid']);

    Livewire::test(ListTravelExpenses::class)
        ->assertSee('Madrid → Berlin')
        ->assertSee('Berlin ← Madrid');

    $this->assertNotSame($departure->id, $return->id);
});

test('reimbursement badge is success under the limit and danger over it', function (): void {
    $countryId = financeCountryId();
    $under = financeParticipation($this->project, countryId: $countryId);
    $over = financeParticipation($this->project, countryId: $countryId, name: 'Big Spender');

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
});

test('sorting still works for a stacked column', function (): void {
    $participation = financeParticipation($this->project);

    $cheap = TravelExpense::factory()->forParticipant($participation)->costing(50)->create();
    $pricey = TravelExpense::factory()->forParticipant($participation)->costing(500)->create();

    Livewire::test(ListTravelExpenses::class)
        ->sortTable('cost_eur')
        ->assertCanSeeTableRecords([$cheap, $pricey], inOrder: true)
        ->sortTable('cost_eur', 'desc')
        ->assertCanSeeTableRecords([$pricey, $cheap], inOrder: true);
});

test('original currency is a tooltip on the euro column', function (): void {
    $participation = financeParticipation($this->project);
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
});

test('reimbursement column reports the participant total against the limit', function (): void {
    $countryId = financeCountryId();
    $participation = financeParticipation($this->project, countryId: $countryId);

    $first = TravelExpense::factory()->forParticipant($participation)->costing(150)->create();
    TravelExpense::factory()->forParticipant($participation)->costing(150)->create();

    CountryLimit::create([
        'project_id' => $this->project->id,
        'country_id' => $countryId,
        'amount_eur' => 275,
    ]);

    $this->assertStringContainsString('€300.00 of €275.00', financeReimbursementDescription($first));
});

test('reimbursement says so when no limit is set', function (): void {
    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->costing(150)->create();

    $this->assertStringContainsString('No limit set', financeReimbursementDescription($expense));
});

test('participant without view all only sees their own expenses', function (): void {
    $someoneElse = financeParticipation($this->project, name: 'Other Traveller');
    $theirs = TravelExpense::factory()->forParticipant($someoneElse)->create(['from' => 'Reykjavik']);

    $own = financeSelfServiceUser($this->organization, $this->project);
    $mine = TravelExpense::factory()->forParticipant($own)->create(['from' => 'Madrid']);

    Livewire::test(ListTravelExpenses::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs])
        ->assertDontSee('Other Traveller')
        ->assertDontSee('Reykjavik');
});

test('participant column and tabs are hidden without view all', function (): void {
    financeParticipation($this->project, countryId: financeCountryId('IT', 'Italy'), name: 'Other Traveller');
    $own = financeSelfServiceUser($this->organization, $this->project);
    TravelExpense::factory()->forParticipant($own)->create();

    $component = Livewire::test(ListTravelExpenses::class);

    $component->assertTableColumnHidden('projectParticipant.participable.name');
    $this->assertSame(['all'], array_keys($component->instance()->getTabs()));
});

test('participant column is visible with view all', function (): void {
    $participation = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($participation)->create();

    Livewire::test(ListTravelExpenses::class)
        ->assertTableColumnVisible('projectParticipant.participable.name');
});

test('self service create forces the users own participation', function (): void {
    $someoneElse = financeParticipation($this->project, name: 'Other Traveller');
    $own = financeSelfServiceUser($this->organization, $this->project);

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
});

test('participant with no participation cannot see other expenses', function (): void {
    $participation = financeParticipation($this->project);
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
});

test('travel type filter narrows the table', function (): void {
    $participation = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($participation)->create(['travel_type' => 'departure', 'from' => 'Vigo']);
    TravelExpense::factory()->forParticipant($participation)->create(['travel_type' => 'return', 'from' => 'Tallinn']);

    Livewire::test(ListTravelExpenses::class)
        ->assertCanSeeTableRecords(TravelExpense::query()->get())
        ->filterTable('group', ['travel_type' => 'departure'])
        ->assertCanSeeTableRecords(TravelExpense::query()->where('travel_type', 'departure')->get())
        ->assertCanNotSeeTableRecords(TravelExpense::query()->where('travel_type', 'return')->get());
});

test('search filter matches participant name and route', function (): void {
    $ana = financeParticipation($this->project);
    $luca = financeParticipation($this->project, name: 'Luca Rossi');

    $anaExpense = TravelExpense::factory()->forParticipant($ana)->create(['from' => 'Madrid', 'to' => 'Berlin']);
    $lucaExpense = TravelExpense::factory()->forParticipant($luca)->create(['from' => 'Rome', 'to' => 'Vienna']);

    Livewire::test(ListTravelExpenses::class)
        ->searchTable('Luca')
        ->assertCanSeeTableRecords([$lucaExpense])
        ->assertCanNotSeeTableRecords([$anaExpense])
        ->searchTable('Madrid')
        ->assertCanSeeTableRecords([$anaExpense])
        ->assertCanNotSeeTableRecords([$lucaExpense]);
});

test('date filter narrows to the range', function (): void {
    $participation = financeParticipation($this->project);

    $early = TravelExpense::factory()->forParticipant($participation)->create(['date' => '2026-01-10']);
    $late = TravelExpense::factory()->forParticipant($participation)->create(['date' => '2026-06-20']);

    Livewire::test(ListTravelExpenses::class)
        ->filterTable('group', ['date_from' => '2026-05-01'])
        ->assertCanSeeTableRecords([$late])
        ->assertCanNotSeeTableRecords([$early])
        ->filterTable('group', ['date_from' => null, 'date_until' => '2026-02-01'])
        ->assertCanSeeTableRecords([$early])
        ->assertCanNotSeeTableRecords([$late]);
});

test('proof filter separates documented from undocumented expenses', function (): void {
    Storage::fake('public');

    $participation = financeParticipation($this->project);
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
});

test('proof of journey toggle ignores payment proof', function (): void {
    Storage::fake('public');

    $participation = financeParticipation($this->project);
    $paymentOnly = TravelExpense::factory()->forParticipant($participation)->create();

    $paymentOnly->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
        ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);

    Livewire::test(ListTravelExpenses::class)
        ->filterTable('group', ['proof_of_journey' => true])
        ->assertCanNotSeeTableRecords([$paymentOnly]);
});

test('type filters default to any and do not narrow the table', function (): void {
    $participation = financeParticipation($this->project);
    $expenses = TravelExpense::factory()->forParticipant($participation)->count(3)->create();

    Livewire::test(ListTravelExpenses::class)
        ->assertCanSeeTableRecords($expenses)
        ->filterTable('group', ['travel_type' => EnumSelect::ANY, 'transportation_type' => EnumSelect::ANY])
        ->assertCanSeeTableRecords($expenses);
});

test('filter group badge ignores defaults and untoggled switches', function (): void {
    $participation = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($participation)->create();

    $component = Livewire::test(ListTravelExpenses::class);

    $this->assertSame(0, financeFilterGroupBadge($component));

    $component->filterTable('group', ['travel_type' => 'departure']);

    $this->assertSame(1, financeFilterGroupBadge($component));
});

test('sort defaults to participant name and offers no travel option', function (): void {
    $participation = financeParticipation($this->project);
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
});

test('expenses sort by participant name across both participable types', function (): void {
    $zoe = financeParticipation($this->project, name: 'Zoe Last');
    $ana = financeParticipation($this->project, name: 'Ana First');

    $user = User::factory()->create(['name' => 'Mid User']);
    $user->joinOrganization($this->organization);
    $mid = $this->project->addParticipant($user, financeCountryId(), ParticipantOrganization::create(['name' => 'Employer']));

    $last = TravelExpense::factory()->forParticipant($zoe)->create();
    $first = TravelExpense::factory()->forParticipant($ana)->create();
    $middle = TravelExpense::factory()->forParticipant($mid)->create();

    Livewire::test(ListTravelExpenses::class)
        ->sortTable('projectParticipant.participable.name')
        ->assertCanSeeTableRecords([$first, $middle, $last], inOrder: true)
        ->sortTable('projectParticipant.participable.name', 'desc')
        ->assertCanSeeTableRecords([$last, $middle, $first], inOrder: true);
});

test('column manager is a filter not a toolbar trigger', function (): void {
    $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();

    $this->assertFalse($table->hasColumnManager());
    $this->assertArrayHasKey('columns', $table->getFilters());
});

test('every column is toggleable so the manager renders checkboxes', function (): void {
    $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();

    $this->assertTrue($table->hasToggleableColumns());

    foreach ($table->getColumns() as $column) {
        $this->assertTrue($column->isToggleable(), $column->getName().' is not toggleable');
    }
});

test('proof columns report attachments and start hidden', function (): void {
    Storage::fake('public');

    $participation = financeParticipation($this->project);
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
});

test('enum filter options carry the enum icons', function (): void {
    $options = EnumSelect::make('travel_type', TravelType::class, 'Any travel', 'lucide-plane-takeoff')
        ->getOptions();

    foreach ([EnumSelect::ANY, 'departure', 'return'] as $value) {
        $this->assertStringContainsString('<svg', $options[$value], "{$value} has no icon");
    }

    $this->assertStringContainsString('Departure', $options['departure']);
    $this->assertStringContainsString('Return', $options['return']);
    $this->assertNotSame($options['departure'], $options['return']);
});

test('all tab groups expenses by country', function (): void {
    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $table = Livewire::test(ListTravelExpenses::class)->instance()->getTable();
    $group = $table->getDefaultGroup();

    $this->assertSame('projectParticipant.country.name', $group?->getId());
    $this->assertSame('🇪🇸 Spain', $group->getTitle($expense->fresh()));
    $this->assertFalse($group->isTitlePrefixedWithLabel());
});

test('travel and transport badges are a column apart from the route', function (): void {
    $participation = financeParticipation($this->project);
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
});

test('seeder only creates expenses for participant participations', function (): void {
    $participation = financeParticipation($this->project);

    $user = User::factory()->create();
    $sending = ParticipantOrganization::create(['name' => 'Employer']);
    $userParticipation = $this->project->addParticipant($user, financeCountryId(), $sending);

    $this->seed(TravelExpenseSeeder::class);

    $this->assertSame(2, TravelExpense::query()
        ->where('project_participant_id', $participation->id)
        ->count());
    $this->assertSame(0, TravelExpense::query()
        ->where('project_participant_id', $userParticipation->id)
        ->count());
    $this->assertDatabaseHas('country_limits', ['project_id' => $this->project->id]);
});

test('export writes the current project rows', function (): void {
    Storage::fake('public');

    $participation = financeParticipation($this->project);
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
    $this->assertStringContainsString('Ana Torres', financeExportedCsv($export));
    $this->assertStringContainsString('Madrid', financeExportedCsv($export));
});

test('export reports whether proof is attached', function (): void {
    Storage::fake('public');

    $participation = financeParticipation($this->project);
    $expense = TravelExpense::factory()->forParticipant($participation)->create();

    $expense->addMedia(UploadedFile::fake()->create('invoice.pdf', 10))
        ->toMediaCollection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT);

    Livewire::test(ListTravelExpenses::class)
        ->callAction('export')
        ->assertHasNoActionErrors();

    $csv = financeExportedCsv(Export::query()->latest('id')->firstOrFail());

    $this->assertStringContainsString('Proof of payment', $csv);
    $this->assertStringContainsString('Proof of journey', $csv);
    $this->assertMatchesRegularExpression('/Yes,+\s*No/', str_replace('"', '', $csv));
});

test('export does not leak another projects expenses', function (): void {
    Storage::fake('public');

    $mine = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($mine)->create(['from' => 'Madrid']);

    $otherProject = Project::factory()->for($this->organization)->create();
    $theirs = financeParticipation($otherProject, name: 'Hidden Person');
    TravelExpense::factory()->forParticipant($theirs)->create(['from' => 'Reykjavik']);

    Livewire::test(ListTravelExpenses::class)
        ->callAction('export')
        ->assertHasNoActionErrors();

    $export = Export::query()->latest('id')->firstOrFail();
    $csv = financeExportedCsv($export);

    $this->assertSame(1, $export->successful_rows);
    $this->assertStringContainsString('Ana Torres', $csv);
    $this->assertStringNotContainsString('Hidden Person', $csv);
    $this->assertStringNotContainsString('Reykjavik', $csv);
});

test('export escapes formula injection in text columns', function (): void {
    Storage::fake('public');

    $participation = financeParticipation($this->project, name: '=SUM(A1:A9)');
    TravelExpense::factory()->forParticipant($participation)->create();

    Livewire::test(ListTravelExpenses::class)
        ->callAction('export')
        ->assertHasNoActionErrors();

    $csv = financeExportedCsv(Export::query()->latest('id')->firstOrFail());

    $this->assertStringContainsString("'=SUM(A1:A9)", $csv);
});

test('deleting a participation removes its expenses', function (): void {
    $participation = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($participation)->create();

    $participation->delete();

    $this->assertSame(0, DB::table('travel_expenses')->count());
});

test('export action is hidden when there are no travel expenses', function (): void {
    Livewire::test(ListTravelExpenses::class)
        ->assertActionHidden('export');
});

test('export action is visible when there is at least one travel expense', function (): void {
    $participation = financeParticipation($this->project);
    TravelExpense::factory()->forParticipant($participation)->create();

    Livewire::test(ListTravelExpenses::class)
        ->assertActionVisible('export');
});

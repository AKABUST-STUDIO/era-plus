<?php

namespace Tests\Feature;

use App\Exports\TravelExpensesExport;
use App\Filament\Project\Resources\TravelExpenses\Pages\CreateTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\EditTravelExpense;
use App\Filament\Project\Resources\TravelExpenses\Pages\ListTravelExpenses;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectCountry;
use App\Models\TravelExpense;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Nnjeim\World\Models\Country;
use Tests\TestCase;

class TravelExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    private Participant $participant;

    private Country $estonia;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('countries')->insert([
            ['iso2' => 'EE', 'iso3' => 'EST', 'name' => 'Estonia', 'status' => 1, 'phone_code' => '372', 'region' => 'Europe', 'subregion' => 'Northern Europe'],
            ['iso2' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany', 'status' => 1, 'phone_code' => '49', 'region' => 'Europe', 'subregion' => 'Western Europe'],
        ]);

        $this->estonia = Country::query()->where('iso2', 'EE')->firstOrFail();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->user);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->project->users()->attach($this->user);

        ProjectCountry::create([
            'project_id' => $this->project->id,
            'country_id' => $this->estonia->id,
            'default_travel_expense_limit' => 500,
        ]);

        $this->participant = Participant::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'country_id' => $this->estonia->id,
            'first_name' => 'Anne',
            'last_name' => 'Tamm',
        ]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_list_loads(): void
    {
        Livewire::test(ListTravelExpenses::class)->assertSuccessful();
    }

    public function test_can_create_travel_expense(): void
    {
        Livewire::test(CreateTravelExpense::class)
            ->fillForm([
                'participant_id' => $this->participant->id,
                'amount' => 120.00,
                'occurred_at' => '2026-05-01',
                'description' => 'Flight to Tallinn',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('travel_expenses', [
            'project_id' => $this->project->id,
            'participant_id' => $this->participant->id,
            'amount' => '120.00',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_requires_participant_amount_date(): void
    {
        Livewire::test(CreateTravelExpense::class)
            ->fillForm([
                'participant_id' => null,
                'amount' => null,
                'occurred_at' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['participant_id', 'amount', 'occurred_at']);
    }

    public function test_can_edit_travel_expense(): void
    {
        $expense = TravelExpense::factory()->forParticipant($this->participant)->create(['amount' => 100]);

        Livewire::test(EditTravelExpense::class, ['record' => $expense->getRouteKey()])
            ->fillForm(['amount' => 250.75])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('travel_expenses', ['id' => $expense->id, 'amount' => '250.75']);
    }

    public function test_exceeds_country_limit_flag(): void
    {
        $under = TravelExpense::factory()->forParticipant($this->participant)->create(['amount' => 400]);
        $over = TravelExpense::factory()->forParticipant($this->participant)->create(['amount' => 600]);

        $this->assertFalse($under->fresh()->exceedsCountryLimit());
        $this->assertTrue($over->fresh()->exceedsCountryLimit());
    }

    public function test_no_limit_means_no_overage(): void
    {
        ProjectCountry::query()
            ->where('project_id', $this->project->id)
            ->where('country_id', $this->estonia->id)
            ->update(['default_travel_expense_limit' => null]);

        $expense = TravelExpense::factory()->forParticipant($this->participant)->create(['amount' => 99999]);

        $this->assertFalse($expense->fresh()->exceedsCountryLimit());
    }

    public function test_excel_export_includes_participant_and_country(): void
    {
        Excel::fake();

        TravelExpense::factory()->forParticipant($this->participant)->create(['amount' => 120]);

        Livewire::test(ListTravelExpenses::class)->call('exportExcel');

        Excel::assertDownloaded(
            sprintf('travel-%s-%s.xlsx', $this->project->slug, now()->format('Y-m-d')),
            function (TravelExpensesExport $export): bool {
                $rows = $export->collection();
                $row = $export->map($rows->first());

                return $rows->count() === 1
                    && $row[1] === 'Anne Tamm'
                    && $row[2] === 'Estonia';
            },
        );
    }

    public function test_export_headings(): void
    {
        $export = new TravelExpensesExport($this->project);

        $this->assertSame(
            ['Date', 'Participant', 'Country', 'Amount', 'Over country limit?', 'Description', 'Created by', 'Created at'],
            $export->headings(),
        );
    }
}

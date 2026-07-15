<?php

namespace Tests\Feature;

use App\Enums\BudgetCategory;
use App\Enums\OrganizationRole;
use App\Filament\Project\Widgets\FinanceOverviewStats;
use App\Models\FinanceEntry;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectCountry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceOverviewStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('countries')->insert([
            ['iso2' => 'EE', 'iso3' => 'EST', 'name' => 'Estonia', 'status' => 1, 'phone_code' => '372', 'region' => 'Europe', 'subregion' => 'Northern Europe'],
        ]);

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_widget_renders(): void
    {
        Livewire::test(FinanceOverviewStats::class)
            ->assertSuccessful();
    }

    public function test_widget_shows_balance_income_expenses(): void
    {
        FinanceEntry::factory()->forProject($this->project)->add()->create([
            'amount' => 1000,
            'cost_category' => null,
        ]);
        FinanceEntry::factory()->forProject($this->project)->subtract()->create([
            'amount' => 300,
            'cost_category' => BudgetCategory::Travel,
        ]);

        Livewire::test(FinanceOverviewStats::class)
            ->assertSee('€700.00')
            ->assertSee('€1,000.00')
            ->assertSee('€300.00');
    }

    public function test_widget_shows_participant_and_country_counts(): void
    {
        $country = DB::table('countries')->first();
        ProjectCountry::create(['project_id' => $this->project->id, 'country_id' => $country->id]);
        Participant::factory()->count(4)->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'country_id' => $country->id,
        ]);

        Livewire::test(FinanceOverviewStats::class)
            ->assertSee('4')
            ->assertSee('1 country');
    }

    public function test_widget_shows_top_expense_categories(): void
    {
        FinanceEntry::factory()->forProject($this->project)->subtract()->create([
            'amount' => 500,
            'cost_category' => BudgetCategory::Travel,
        ]);
        FinanceEntry::factory()->forProject($this->project)->subtract()->create([
            'amount' => 200,
            'cost_category' => BudgetCategory::CourseFees,
        ]);

        Livewire::test(FinanceOverviewStats::class)
            ->assertSee('Travel')
            ->assertSee('Course / training fees');
    }
}

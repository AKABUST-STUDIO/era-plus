<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Filament\Project\Resources\FinanceEntries\Pages\ListFinanceEntries;
use App\Models\FinanceEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceTotalsTest extends TestCase
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
        $this->user->joinProject($this->project);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_total_is_zero_when_no_entries(): void
    {
        $this->assertSame('0.00', $this->project->financeTotal());
    }

    public function test_total_sums_add_entries(): void
    {
        FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 100]);
        FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 50.25]);

        $this->assertSame('150.25', $this->project->financeTotal());
    }

    public function test_total_subtracts_subtract_entries(): void
    {
        FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 500]);
        FinanceEntry::factory()->forProject($this->project)->subtract()->create(['amount' => 125.75]);
        FinanceEntry::factory()->forProject($this->project)->subtract()->create(['amount' => 24.25]);

        $this->assertSame('350.00', $this->project->financeTotal());
    }

    public function test_total_can_go_negative(): void
    {
        FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 100]);
        FinanceEntry::factory()->forProject($this->project)->subtract()->create(['amount' => 250]);

        $this->assertSame('-150.00', $this->project->financeTotal());
    }

    public function test_total_ignores_other_projects(): void
    {
        $other = Project::factory()->for($this->organization)->create();
        FinanceEntry::factory()->forProject($other)->add()->create(['amount' => 999]);
        FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 10]);

        $this->assertSame('10.00', $this->project->financeTotal());
    }

    public function test_total_recomputes_after_change(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 100]);
        $this->assertSame('100.00', $this->project->fresh()->financeTotal());

        $entry->update(['amount' => 250]);
        $this->assertSame('250.00', $this->project->fresh()->financeTotal());

        $entry->delete();
        $this->assertSame('0.00', $this->project->fresh()->financeTotal());
    }

    public function test_list_page_shows_total_in_subheading(): void
    {
        FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 1234.56]);

        Livewire::test(ListFinanceEntries::class)
            ->assertSee('Project total: €1,234.56');
    }
}

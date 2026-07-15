<?php

namespace Tests\Feature;

use App\Enums\BudgetCategory;
use App\Enums\FinanceOperation;
use App\Enums\OrganizationRole;
use App\Filament\Project\Resources\FinanceEntries\Pages\CreateFinanceEntry;
use App\Models\FinanceEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetCategoryTest extends TestCase
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

    public function test_cost_category_casts_to_enum(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->create([
            'cost_category' => BudgetCategory::Travel,
        ]);

        $this->assertSame(BudgetCategory::Travel, $entry->fresh()->cost_category);
    }

    public function test_subtract_without_category_is_flagged(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->subtract()->create([
            'cost_category' => null,
        ]);

        $this->assertTrue($entry->isFlagged());
    }

    public function test_subtract_with_category_is_not_flagged(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->subtract()->create([
            'cost_category' => BudgetCategory::Travel,
        ]);

        $this->assertFalse($entry->isFlagged());
    }

    public function test_add_without_category_is_not_flagged(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->add()->create([
            'cost_category' => null,
        ]);

        $this->assertFalse($entry->isFlagged());
    }

    public function test_create_form_requires_category_for_subtract(): void
    {
        Livewire::test(CreateFinanceEntry::class)
            ->fillForm([
                'operation' => FinanceOperation::Subtract->value,
                'amount' => 50,
                'occurred_at' => '2026-05-01',
                'cost_category' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['cost_category']);
    }

    public function test_create_form_allows_add_without_category(): void
    {
        Livewire::test(CreateFinanceEntry::class)
            ->fillForm([
                'operation' => FinanceOperation::Add->value,
                'amount' => 100,
                'occurred_at' => '2026-05-01',
                'cost_category' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('finance_entries', [
            'project_id' => $this->project->id,
            'operation' => FinanceOperation::Add->value,
            'cost_category' => null,
        ]);
    }

    public function test_totals_by_category(): void
    {
        FinanceEntry::factory()->forProject($this->project)->subtract()
            ->create(['cost_category' => BudgetCategory::Travel, 'amount' => 100]);
        FinanceEntry::factory()->forProject($this->project)->subtract()
            ->create(['cost_category' => BudgetCategory::Travel, 'amount' => 50]);
        FinanceEntry::factory()->forProject($this->project)->subtract()
            ->create(['cost_category' => BudgetCategory::CourseFees, 'amount' => 200]);
        FinanceEntry::factory()->forProject($this->project)->add()
            ->create(['cost_category' => BudgetCategory::Travel, 'amount' => 30]);

        $totals = $this->project->fresh()->financeTotalsByCategory();

        $this->assertSame('-120.00', $totals[BudgetCategory::Travel->value]);
        $this->assertSame('-200.00', $totals[BudgetCategory::CourseFees->value]);
        $this->assertSame('0.00', $totals[BudgetCategory::OrganizationalSupport->value]);
    }
}

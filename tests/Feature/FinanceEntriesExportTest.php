<?php

namespace Tests\Feature;

use App\Enums\FinanceEntry\BudgetCategory;
use App\Enums\FinanceEntry\FinanceOperation;
use App\Enums\Organization\OrganizationRole;
use App\Exports\FinanceEntriesExport;
use App\Filament\Project\Resources\FinanceEntries\Pages\ListFinanceEntries;
use App\Models\FinanceEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FinanceEntriesExportTest extends TestCase
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

    public function test_export_has_expected_headings(): void
    {
        $export = new FinanceEntriesExport($this->project);

        $this->assertSame(
            ['Date', 'Operation', 'Category', 'Amount', 'Signed amount', 'Description', 'Created by', 'Created at'],
            $export->headings(),
        );
    }

    public function test_export_maps_an_entry_row(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->subtract()->create([
            'amount' => 125.50,
            'occurred_at' => '2026-04-10',
            'description' => 'Conference fee',
            'cost_category' => BudgetCategory::Travel,
            'created_by' => $this->user->id,
        ]);

        $row = (new FinanceEntriesExport($this->project))->map($entry->fresh());

        $this->assertSame('2026-04-10', $row[0]);
        $this->assertSame('Subtract', $row[1]);
        $this->assertSame('Travel', $row[2]);
        $this->assertSame('125.50', $row[3]);
        $this->assertSame('-125.50', $row[4]);
        $this->assertSame('Conference fee', $row[5]);
        $this->assertSame($this->user->name, $row[6]);
    }

    public function test_export_collection_only_includes_current_project_entries(): void
    {
        $other = Project::factory()->for($this->organization)->create();
        FinanceEntry::factory()->forProject($other)->count(3)->create();
        FinanceEntry::factory()->forProject($this->project)->add()->count(2)->create();

        $entries = (new FinanceEntriesExport($this->project))->collection();

        $this->assertCount(2, $entries);
    }

    public function test_list_page_export_action_downloads_xlsx(): void
    {
        Excel::fake();

        FinanceEntry::factory()->forProject($this->project)->add()->create(['amount' => 100]);

        Livewire::test(ListFinanceEntries::class)
            ->call('exportExcel');

        Excel::assertDownloaded(
            sprintf('finance-%s-%s.xlsx', $this->project->slug, now()->format('Y-m-d')),
            function (FinanceEntriesExport $export): bool {
                return $export->collection()->count() === 1;
            },
        );
    }

    public function test_collection_orders_by_occurred_at(): void
    {
        FinanceEntry::factory()->forProject($this->project)->create(['occurred_at' => '2026-03-01', 'operation' => FinanceOperation::Add, 'amount' => 1]);
        FinanceEntry::factory()->forProject($this->project)->create(['occurred_at' => '2026-01-01', 'operation' => FinanceOperation::Add, 'amount' => 2]);
        FinanceEntry::factory()->forProject($this->project)->create(['occurred_at' => '2026-02-01', 'operation' => FinanceOperation::Add, 'amount' => 3]);

        $entries = (new FinanceEntriesExport($this->project))->collection();

        $this->assertSame(['2026-01-01', '2026-02-01', '2026-03-01'], $entries->pluck('occurred_at')->map->toDateString()->all());
    }
}

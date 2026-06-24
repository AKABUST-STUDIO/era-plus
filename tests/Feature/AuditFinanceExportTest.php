<?php

namespace Tests\Feature;

use App\Enums\BudgetCategory;
use App\Exports\AuditFinanceEntriesSheet;
use App\Exports\AuditFinanceExport;
use App\Filament\Project\Resources\FinanceEntries\Pages\ListFinanceEntries;
use App\Models\FinanceEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AuditFinanceExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->user);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->project->users()->attach($this->user);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_export_returns_four_sheets(): void
    {
        $export = new AuditFinanceExport($this->project);

        $this->assertCount(4, $export->sheets());
    }

    public function test_entries_sheet_includes_audit_columns(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->subtract()->create([
            'amount' => 250,
            'cost_category' => BudgetCategory::Travel,
            'occurred_at' => '2026-05-01',
            'description' => 'Bus ticket',
            'created_by' => $this->user->id,
        ]);

        $entry->addMedia(UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf'))
            ->toMediaCollection(FinanceEntry::DOCUMENTS_COLLECTION);

        $sheet = new AuditFinanceEntriesSheet($this->project);

        $this->assertSame(
            ['Entry ID', 'Date', 'Category', 'Operation', 'Amount', 'Signed amount', 'Description', 'Created by', 'Created at', 'Updated at', 'Document count', 'Flagged?'],
            $sheet->headings(),
        );

        $row = $sheet->map($entry->fresh());

        $this->assertSame((string) $entry->id, $row[0]);
        $this->assertSame('Travel', $row[2]);
        $this->assertSame('Subtract', $row[3]);
        $this->assertSame('-250.00', $row[5]);
        $this->assertSame('1', $row[10]);
        $this->assertSame('no', $row[11]);
    }

    public function test_audit_export_downloads_xlsx(): void
    {
        Excel::fake();

        FinanceEntry::factory()->forProject($this->project)->create();

        Livewire::test(ListFinanceEntries::class)->call('exportAudit');

        Excel::assertDownloaded(
            sprintf('audit-finance-%s-%s.xlsx', $this->project->slug, now()->format('Y-m-d')),
        );
    }
}

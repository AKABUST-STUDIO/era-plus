<?php

namespace Tests\Feature;

use App\Enums\FinanceOperation;
use App\Enums\OrganizationRole;
use App\Filament\Project\Resources\FinanceEntries\FinanceEntryResource;
use App\Filament\Project\Resources\FinanceEntries\Pages\CreateFinanceEntry;
use App\Filament\Project\Resources\FinanceEntries\Pages\EditFinanceEntry;
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

class FinanceEntryCrudTest extends TestCase
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

    public function test_list_page_loads(): void
    {
        Livewire::test(ListFinanceEntries::class)
            ->assertSuccessful();
    }

    public function test_finance_entry_can_be_created(): void
    {
        Livewire::test(CreateFinanceEntry::class)
            ->fillForm([
                'operation' => FinanceOperation::Add->value,
                'amount' => 250.50,
                'occurred_at' => '2026-05-01',
                'description' => 'Initial deposit',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('finance_entries', [
            'organization_id' => $this->organization->id,
            'project_id' => $this->project->id,
            'operation' => FinanceOperation::Add->value,
            'amount' => '250.50',
            'description' => 'Initial deposit',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_requires_amount_and_operation(): void
    {
        Livewire::test(CreateFinanceEntry::class)
            ->fillForm([
                'operation' => null,
                'amount' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['operation', 'amount']);
    }

    public function test_create_rejects_zero_or_negative_amount(): void
    {
        Livewire::test(CreateFinanceEntry::class)
            ->fillForm([
                'operation' => FinanceOperation::Add->value,
                'amount' => 0,
                'occurred_at' => '2026-05-01',
            ])
            ->call('create')
            ->assertHasFormErrors(['amount']);
    }

    public function test_finance_entry_can_be_edited(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->add()->create([
            'amount' => 100,
            'description' => 'Old',
        ]);

        Livewire::test(EditFinanceEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm([
                'amount' => 175.25,
                'description' => 'Updated',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('finance_entries', [
            'id' => $entry->id,
            'amount' => '175.25',
            'description' => 'Updated',
        ]);
    }

    public function test_finance_entry_can_be_deleted(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->create();

        Livewire::test(EditFinanceEntry::class, ['record' => $entry->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('finance_entries', [
            'id' => $entry->id,
        ]);
    }

    public function test_entries_are_scoped_to_active_project(): void
    {
        $otherProject = Project::factory()->for($this->organization)->create();
        FinanceEntry::factory()->forProject($otherProject)->count(2)->create();
        FinanceEntry::factory()->forProject($this->project)->count(3)->create();

        $records = FinanceEntryResource::getEloquentQuery()->get();

        $this->assertCount(3, $records);
        $this->assertTrue($records->every(fn (FinanceEntry $e) => $e->project_id === $this->project->id));
    }
}

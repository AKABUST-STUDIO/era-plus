<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\FinanceEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Tests\TestCase;

class FinanceEntryDocumentsTest extends TestCase
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
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_finance_entry_implements_has_media(): void
    {
        $this->assertInstanceOf(HasMedia::class, new FinanceEntry);
    }

    public function test_documents_collection_is_registered(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->create();

        $entry->addMedia(UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'))
            ->toMediaCollection(FinanceEntry::DOCUMENTS_COLLECTION);

        $this->assertCount(1, $entry->getMedia(FinanceEntry::DOCUMENTS_COLLECTION));
    }

    public function test_multiple_documents_can_be_attached(): void
    {
        $entry = FinanceEntry::factory()->forProject($this->project)->create();

        $entry->addMedia(UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'))
            ->toMediaCollection(FinanceEntry::DOCUMENTS_COLLECTION);
        $entry->addMedia(UploadedFile::fake()->image('receipt.jpg', 800, 600))
            ->toMediaCollection(FinanceEntry::DOCUMENTS_COLLECTION);

        $this->assertCount(2, $entry->fresh()->getMedia(FinanceEntry::DOCUMENTS_COLLECTION));
    }

    public function test_documents_are_isolated_between_entries(): void
    {
        $first = FinanceEntry::factory()->forProject($this->project)->create();
        $second = FinanceEntry::factory()->forProject($this->project)->create();

        $first->addMedia(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'))
            ->toMediaCollection(FinanceEntry::DOCUMENTS_COLLECTION);

        $this->assertCount(1, $first->fresh()->getMedia(FinanceEntry::DOCUMENTS_COLLECTION));
        $this->assertCount(0, $second->fresh()->getMedia(FinanceEntry::DOCUMENTS_COLLECTION));
    }
}

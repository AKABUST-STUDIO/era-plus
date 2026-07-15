<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Enums\ProjectTaskStatus;
use App\Filament\Project\Resources\ProjectTasks\Pages\CreateProjectTask;
use App\Filament\Project\Resources\ProjectTasks\Pages\EditProjectTask;
use App\Filament\Project\Resources\ProjectTasks\Pages\ListProjectTasks;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $assignee;

    private Organization $organization;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Coordinator']);
        $this->assignee = User::factory()->create(['name' => 'Anne']);
        $this->organization = Organization::factory()->create();
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);
        $this->assignee->joinOrganization($this->organization);
        $this->project = Project::factory()->for($this->organization)->create();
        $this->user->joinProject($this->project, ProjectRole::Coordinator);
        $this->assignee->joinProject($this->project);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('project'));
        Filament::setTenant($this->project);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    public function test_list_loads(): void
    {
        Livewire::test(ListProjectTasks::class)->assertSuccessful();
    }

    public function test_task_can_be_assigned(): void
    {
        Livewire::test(CreateProjectTask::class)
            ->fillForm([
                'title' => 'Draft progress report',
                'assigned_to' => $this->assignee->id,
                'status' => ProjectTaskStatus::Open->value,
                'due_date' => '2026-12-31',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $this->project->id,
            'assigned_to' => $this->assignee->id,
            'assigned_by' => $this->user->id,
            'title' => 'Draft progress report',
            'status' => ProjectTaskStatus::Open->value,
        ]);
    }

    public function test_completing_task_sets_completed_at(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'assigned_to' => $this->assignee->id,
        ]);

        Livewire::test(EditProjectTask::class, ['record' => $task->getRouteKey()])
            ->fillForm(['status' => ProjectTaskStatus::Completed->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $task->fresh();

        $this->assertSame(ProjectTaskStatus::Completed, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_reopening_task_clears_completed_at(): void
    {
        $task = ProjectTask::factory()->completed()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'assigned_to' => $this->assignee->id,
        ]);

        $task->update(['status' => ProjectTaskStatus::Open]);

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_is_overdue(): void
    {
        $overdue = ProjectTask::factory()->overdue()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'assigned_to' => $this->assignee->id,
        ]);
        $completed = ProjectTask::factory()->completed()->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'assigned_to' => $this->assignee->id,
            'due_date' => now()->subWeek()->format('Y-m-d'),
        ]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($completed->isOverdue());
    }

    public function test_tasks_isolated_by_project(): void
    {
        $other = Project::factory()->for($this->organization)->create();
        ProjectTask::factory()->count(2)->create([
            'project_id' => $other->id,
            'organization_id' => $this->organization->id,
            'assigned_to' => $this->assignee->id,
        ]);
        ProjectTask::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'organization_id' => $this->organization->id,
            'assigned_to' => $this->assignee->id,
        ]);

        $this->assertCount(3, ProjectTask::query()->get());
    }
}

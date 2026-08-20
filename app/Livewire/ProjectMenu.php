<?php

namespace App\Livewire;

use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Filament\Panels\PanelPage;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProjectMenu extends Component
{
    protected string $view = 'livewire.project-menu';

    public string $page = '';

    public function mount(): void
    {
        $this->page = PanelPage::currentSlug();
    }

    public function render(): View
    {
        $user = auth()->user();
        $organization = OrganizationService::current();

        if (! $user instanceof User || ! $organization instanceof Organization) {
            return view($this->view, [
                'organization' => null,
                'currentProject' => null,
                'items' => collect(),
                'createUrl' => null,
                'clearUrl' => null,
            ]);
        }

        $currentProject = ProjectService::current();

        $items = ProjectService::projectsFor($user, $organization)->map(fn (Project $project) => [
            'name' => $project->name,
            'url' => ProjectService::urlFor($project, $this->page),
            'image' => $project->getAvatarUrl(),
            'isCurrent' => $currentProject instanceof Project && $project->is($currentProject),
        ]);

        $canCreate = $user->can('create', Project::class);

        return view($this->view, [
            'organization' => $organization,
            'currentProject' => $currentProject,
            'items' => $items,
            'createUrl' => $canCreate ? ProjectService::createUrlFor($organization) : null,
            'clearUrl' => OrganizationService::urlFor($organization, $this->page),
        ]);
    }
}

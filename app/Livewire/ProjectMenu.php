<?php

namespace App\Livewire;

use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProjectMenu extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $organization = OrganizationService::current();
        $isSidebarFullyCollapsibleOnDesktop = Filament::isSidebarFullyCollapsibleOnDesktop();

        if (! $user instanceof User || ! $organization instanceof Organization) {
            return view('livewire.project-menu', [
                'organization' => null,
                'currentProject' => null,
                'items' => collect(),
                'createUrl' => null,
                'isSidebarFullyCollapsibleOnDesktop' => $isSidebarFullyCollapsibleOnDesktop,
            ]);
        }

        $currentProject = ProjectService::current();

        $items = ProjectService::projectsFor($user, $organization)->map(fn (Project $project) => [
            'name' => $project->name,
            'slug' => $project->slug,
            'url' => ProjectService::urlFor($project),
            'image' => $project->getAvatarUrl(),
            'isCurrent' => $currentProject instanceof Project && $project->is($currentProject),
        ]);

        return view('livewire.project-menu', [
            'organization' => $organization,
            'currentProject' => $currentProject,
            'items' => $items,
            'createUrl' => ProjectService::createUrlFor($organization),
            'isSidebarFullyCollapsibleOnDesktop' => $isSidebarFullyCollapsibleOnDesktop,
        ]);
    }
}

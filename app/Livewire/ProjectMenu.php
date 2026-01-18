<?php

namespace App\Livewire;

use App\Facades\OrganizationService;
use App\Facades\ProjectService;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProjectMenu extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $organization = OrganizationService::current();

        if (! $user instanceof User || ! $organization instanceof Organization) {
            return view('livewire.project-menu', [
                'organization' => null,
                'items' => collect(),
                'createUrl' => null,
            ]);
        }

        $items = ProjectService::projectsFor($user, $organization)->map(fn (Project $project) => [
            'name' => $project->name,
            'url' => ProjectService::urlFor($project),
            'image' => $project->getAvatarUrl(),
        ]);

        return view('livewire.project-menu', [
            'organization' => $organization,
            'items' => $items,
            'createUrl' => ProjectService::createUrlFor($organization),
        ]);
    }
}

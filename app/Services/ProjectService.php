<?php

namespace App\Services;

use App\Filament\Project\Pages\Overview;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Providers\Filament\OrganizationPanelProvider;
use App\Providers\Filament\ProjectPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class ProjectService
{
    private const SESSION_KEY = 'selected_project_id';

    public function current(): ?Project
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Project ? $tenant : $this->selected();
    }

    /**
     * @return Collection<int, Project>
     */
    public function projectsFor(User $user, Organization $organization): Collection
    {
        return $user
            ->projects()
            ->where('projects.organization_id', $organization->id)
            ->orderBy('name')
            ->get()
            ->each(fn (Project $project) => $project->setRelation('organization', $organization));
    }

    public function urlFor(Project $project): string
    {
        return Overview::getUrl(
            ['organization' => $project->organization->slug],
            panel: ProjectPanelProvider::PANEL_ID,
            tenant: $project,
        );
    }

    public function createUrlFor(Organization $organization): string
    {
        return ProjectResource::getUrl(
            'create',
            panel: OrganizationPanelProvider::PANEL_ID,
            tenant: $organization,
        );
    }

    public function remember(Project $project): void
    {
        session()->put(self::SESSION_KEY, $project->id);
    }

    public function selected(): ?Project
    {
        $id = session()->get(self::SESSION_KEY);

        return $id ? Project::find($id) : null;
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
